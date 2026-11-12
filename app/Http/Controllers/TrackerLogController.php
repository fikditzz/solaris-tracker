<?php

namespace App\Http\Controllers;

use App\Models\TrackerLog;
use App\Models\WeatherLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TrackerLogController extends Controller
{
    private const STORED_MODES = ['Automatic', 'Manual', 'Stop'];

    /** Chart ranges: window length in hours and bucket size in seconds. */
    private const SERIES_RANGES = [
        '24h' => ['hours' => 24, 'bucket' => 900],
        '7d' => ['hours' => 168, 'bucket' => 7200],
        '30d' => ['hours' => 720, 'bucket' => 86400],
    ];

    private const EPOCH_2000 = 946684800;

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'panel_id' => 'nullable|string',
                'irradiance' => 'nullable|numeric',
                'power_output' => 'nullable|numeric',
                'voltage' => 'nullable|numeric',
                'current' => 'nullable|numeric',
                'battery_percentage' => 'nullable|numeric',
                'battery_voltage' => 'nullable|numeric',
                'battery_current' => 'nullable|numeric',
                'azimuth' => 'nullable|numeric',
                'elevation' => 'nullable|numeric',
                'target_azimuth' => 'nullable|numeric',
                'target_elevation' => 'nullable|numeric',
                'ldr_nw' => 'nullable|numeric',
                'ldr_ne' => 'nullable|numeric',
                'ldr_sw' => 'nullable|numeric',
                'ldr_se' => 'nullable|numeric',
                'motor1_load' => 'nullable|numeric',
                'motor2_load' => 'nullable|numeric',
                'tracking_mode' => 'nullable|string',
                'weather_status' => 'nullable|string',
                'firmware_version' => 'nullable|string|max:32',
                'created_at' => 'nullable|date',
            ]);

            $panelId = $validated['panel_id'] ?? 'panel1';
            $validated['panel_id'] = $panelId;

            // A mode chosen from the dashboard wins; otherwise keep what the device reports.
            $reported = $validated['tracking_mode'] ?? null;
            $mode = Cache::get('tracking_mode_' . $panelId);
            if (! in_array($mode, self::STORED_MODES, true)) {
                $mode = in_array($reported, self::STORED_MODES, true) ? $reported : 'Automatic';
            }
            $validated['tracking_mode'] = $mode;

            // Devices cannot see the sky, so the recorded site weather is what gets stored.
            $validated['weather_status'] = WeatherLog::currentDescription();

            $log = TrackerLog::create($validated);

            $command = Cache::pull('manual_command_' . $panelId);
            $target = $mode === 'Manual' ? Cache::pull('manual_target_' . $panelId) : null;

            return response()->json([
                'success' => true,
                'message' => 'Data logged successfully',
                'data' => $log,
                'mode' => $mode,
                'command' => $command,
                'target' => $target
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error saving tracker data from ESP32: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function latest(Request $request)
    {
        $panelId = $request->query('panel_id', 'panel1');
        $latest = TrackerLog::where('panel_id', $panelId)->latest()->first();
        return response()->json([
            'success' => true,
            'data' => $latest
        ]);
    }

    public function setMode(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:Automatic,Manual,Reset,Stop',
            'panel_id' => 'nullable|string'
        ]);

        $panelId = $validated['panel_id'] ?? 'panel1';
        $panels = $panelId === 'all' ? ['panel1', 'panel2'] : [$panelId];

        foreach ($panels as $panel) {
            // Reset is a one-off move, not a lasting mode, so it must not stick to later logs.
            if ($validated['mode'] === 'Reset') {
                Cache::put('manual_command_' . $panel, 'Reset', now()->addMinutes(1));
                continue;
            }

            Cache::put('tracking_mode_' . $panel, $validated['mode']);

            // A position typed for Manual must not fire after leaving and re-entering it.
            if ($validated['mode'] !== 'Manual') {
                Cache::forget('manual_target_' . $panel);
            }

            $latest = TrackerLog::where('panel_id', $panel)->latest()->first();
            if ($latest) {
                $latest->tracking_mode = $validated['mode'];
                $latest->save();
            }
        }

        return response()->json(['success' => true, 'mode' => $validated['mode']]);
    }

    public function sendCommand(Request $request)
    {
        $validated = $request->validate([
            'command' => 'required|string|in:Up,Down,Left,Right,Reset',
            'panel_id' => 'nullable|string'
        ]);

        $panelId = $validated['panel_id'] ?? 'panel1';
        Cache::put('manual_command_' . $panelId, $validated['command'], now()->addMinutes(1));

        return response()->json(['success' => true, 'command' => $validated['command']]);
    }

    /**
     * Queues a position for the device; an axis left out stays where it is.
     */
    public function sendTarget(Request $request)
    {
        $validated = $request->validate([
            'azimuth' => 'nullable|required_without:elevation|numeric|between:0,360',
            'elevation' => 'nullable|required_without:azimuth|numeric|between:0,180',
            'panel_id' => 'nullable|string'
        ]);

        $target = [];
        foreach (['azimuth', 'elevation'] as $axis) {
            if (isset($validated[$axis])) {
                $target[$axis] = (float) $validated[$axis];
            }
        }

        $panelId = $validated['panel_id'] ?? 'panel1';
        Cache::put('manual_target_' . $panelId, $target, now()->addMinutes(1));

        return response()->json(['success' => true, 'target' => $target]);
    }

    /**
     * Returns every matching log unless per_page is given, then one page plus meta.
     */
    public function logs(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => ['nullable', Rule::in(TrackerLog::STATUSES)],
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ]);

        $panelId = $request->query('panel_id', 'panel1');
        $query = TrackerLog::where('panel_id', $panelId)->latest();
        $timezone = config('tracker.timezone');

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->start_date, $timezone)->startOfDay()->utc());
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->end_date, $timezone)->endOfDay()->utc());
        }
        if ($request->filled('status')) {
            $query->whereStatus($request->status, $panelId);
        }

        if (! $request->filled('per_page')) {
            return response()->json(['success' => true, 'data' => $query->get()]);
        }

        $page = $query->paginate((int) $request->per_page);

        return response()->json([
            'success' => true,
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * Time-bucketed averages for charts, aggregated in SQL so long ranges stay light.
     * Energy is an estimate: average power x time between a bucket's first and last sample.
     */
    public function series(Request $request)
    {
        $validated = $request->validate([
            'range' => ['nullable', Rule::in(array_keys(self::SERIES_RANGES))],
        ]);

        $range = self::SERIES_RANGES[$validated['range'] ?? '24h'];
        $panelId = $request->query('panel_id', 'panel1');
        $size = $range['bucket'];
        // Shift buckets so daily ones start at local midnight instead of UTC midnight.
        $offset = Carbon::now(config('tracker.timezone'))->utcOffset() * 60;

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $seconds = $isSqlite
            ? "(CAST(strftime('%s', created_at) AS INTEGER) - " . self::EPOCH_2000 . ')'
            : "TIMESTAMPDIFF(SECOND, '2000-01-01 00:00:00', created_at)";
        $bucket = $isSqlite
            ? "(($seconds + $offset) / $size)"
            : "(($seconds + $offset) DIV $size)";

        $rows = TrackerLog::where('panel_id', $panelId)
            ->where('created_at', '>=', now()->subHours($range['hours']))
            ->selectRaw("$bucket AS bucket")
            ->selectRaw('AVG(voltage) AS voltage, AVG(`current`) AS `current`, AVG(power_output) AS power')
            ->selectRaw('MAX(power_output) AS peak_power, COUNT(*) AS samples')
            ->selectRaw("MAX($seconds) - MIN($seconds) AS span_seconds")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->toBase()
            ->get();

        $points = $rows->map(function ($row) use ($size, $offset) {
            $power = (float) $row->power;
            $timestamp = (int) $row->bucket * $size - $offset + self::EPOCH_2000;

            return [
                't' => Carbon::createFromTimestampUTC($timestamp)->toIso8601ZuluString(),
                'voltage' => round((float) $row->voltage, 2),
                'current' => round((float) $row->current, 2),
                'power' => round($power, 2),
                'energy_wh' => round($power * ((int) $row->span_seconds / 3600), 2),
                'peak_power' => round((float) $row->peak_power, 2),
                'samples' => (int) $row->samples,
            ];
        })->values();

        $samples = $points->sum('samples');

        return response()->json([
            'success' => true,
            'data' => $points,
            'summary' => [
                'samples' => $samples,
                'peak_power' => $points->max('peak_power') ?? 0,
                'avg_power' => $samples > 0
                    ? round($points->sum(fn ($point) => $point['power'] * $point['samples']) / $samples, 2)
                    : 0,
                'energy_wh' => round($points->sum('energy_wh'), 2),
            ],
        ]);
    }

    public function weatherLatest()
    {
        $latest = WeatherLog::latestReading();

        return response()->json([
            'success' => true,
            'data' => $latest,
            'stale' => $latest === null
                || $latest->observed_at->lt(now()->subMinutes(WeatherLog::STALE_AFTER_MINUTES)),
        ]);
    }

    /**
     * Installed firmware per array (as last reported) next to the version published in the OTA repo.
     */
    public function firmware()
    {
        $data = [];
        foreach (array_keys(config('tracker.firmware.version_urls')) as $panelId) {
            $reported = TrackerLog::where('panel_id', $panelId)
                ->whereNotNull('firmware_version')
                ->latest()
                ->first(['firmware_version', 'created_at']);

            $installed = $reported?->firmware_version;
            $latest = $this->latestFirmwareVersion($panelId);

            $data[$panelId] = [
                'installed' => $installed,
                'latest' => $latest,
                'state' => match (true) {
                    $installed === null || $latest === null => 'unknown',
                    $installed === $latest => 'up_to_date',
                    default => 'update_available',
                },
                'last_seen' => $reported?->created_at,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'repo_url' => config('tracker.firmware.repo_url'),
        ]);
    }

    /**
     * Reads version.txt from the OTA repo. A miss is remembered for a minute so a
     * GitHub outage is not hit on every dashboard refresh.
     */
    private function latestFirmwareVersion(string $panelId): ?string
    {
        $key = 'firmware_latest_' . $panelId;
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached === '' ? null : $cached;
        }

        try {
            $version = trim(Http::timeout(5)
                ->get(config('tracker.firmware.version_urls.' . $panelId))
                ->throw()
                ->body());
            if (! preg_match('/^[0-9A-Za-z.+_-]{1,32}$/', $version)) {
                $version = null;
            }
        } catch (\Throwable $e) {
            Log::warning('Could not read the latest firmware version: ' . $e->getMessage());
            $version = null;
        }

        Cache::put($key, $version ?? '', $version === null ? now()->addMinute() : now()->addMinutes(5));

        return $version;
    }

    public function weatherHistory(Request $request)
    {
        $validated = $request->validate([
            'range' => ['nullable', Rule::in(array_keys(self::SERIES_RANGES))],
        ]);

        $range = self::SERIES_RANGES[$validated['range'] ?? '24h'];

        return response()->json([
            'success' => true,
            'data' => WeatherLog::where('observed_at', '>=', now()->subHours($range['hours']))
                ->orderBy('observed_at')
                ->get(),
        ]);
    }

    public function resetData(Request $request)
    {
        $panelId = $request->input('panel_id');
        if ($panelId && $panelId !== 'all') {
            TrackerLog::where('panel_id', $panelId)->delete();
            $message = 'Logs for ' . $panelId . ' deleted.';
        } else {
            TrackerLog::truncate();
            $message = 'Database truncated completely.';
        }

        Cache::flush();
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
