<?php

namespace Tests\Feature;

use App\Models\TrackerLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TrackerApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(array $attributes = []): TrackerLog
    {
        return TrackerLog::create(array_merge([
            'panel_id' => 'panel1',
            'voltage' => 300,
            'current' => 10,
            'power_output' => 3000,
            'azimuth' => 180,
            'elevation' => 45,
            'ldr_nw' => 500,
            'ldr_ne' => 500,
            'ldr_sw' => 500,
            'ldr_se' => 500,
            'tracking_mode' => 'Automatic',
        ], $attributes));
    }

    public function test_logs_without_per_page_still_returns_every_row(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->makeLog();
        }

        $response = $this->getJson('/api/tracker/logs?panel_id=panel1')->assertOk();

        $response->assertJsonCount(3, 'data');
        $response->assertJsonMissingPath('meta');
    }

    public function test_logs_with_per_page_returns_one_page_and_meta(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->makeLog();
        }

        $this->getJson('/api/tracker/logs?panel_id=panel1&per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_status_filter_matches_the_status_shown_on_each_row(): void
    {
        $this->makeLog();
        $this->makeLog(['ldr_nw' => 900, 'ldr_ne' => 100]);
        $this->makeLog(['tracking_mode' => 'Manual']);
        $this->makeLog(['tracking_mode' => 'Stop']);

        foreach (TrackerLog::STATUSES as $status) {
            $rows = $this->getJson('/api/tracker/logs?panel_id=panel1&status=' . urlencode($status))
                ->assertOk()
                ->json('data');

            $this->assertCount(1, $rows, $status);
            $this->assertSame($status, $rows[0]['status']);
        }
    }

    public function test_panel_without_light_readings_is_not_reported_as_locked(): void
    {
        $log = $this->makeLog(['ldr_nw' => 0, 'ldr_ne' => 0, 'ldr_sw' => 0, 'ldr_se' => 0]);

        $this->assertSame(TrackerLog::STATUS_TRACKING, $log->status);
    }

    public function test_single_axis_status_compares_target_and_current_angle(): void
    {
        $locked = $this->makeLog(['panel_id' => 'panel2', 'azimuth' => 40, 'elevation' => 41]);
        $moving = $this->makeLog(['panel_id' => 'panel2', 'azimuth' => 40, 'elevation' => 60]);

        $this->assertSame(TrackerLog::STATUS_LOCKED, $locked->status);
        $this->assertSame(TrackerLog::STATUS_TRACKING, $moving->status);
    }

    public function test_date_filter_uses_local_days(): void
    {
        // 2026-11-03 17:00 UTC is 2026-11-04 01:00 in Asia/Makassar.
        $this->makeLog(['created_at' => Carbon::parse('2026-11-03 17:00:00', 'UTC')]);
        $this->makeLog(['created_at' => Carbon::parse('2026-11-03 15:00:00', 'UTC')]);

        $this->getJson('/api/tracker/logs?panel_id=panel1&start_date=2026-11-04&end_date=2026-11-04')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_invalid_date_is_rejected(): void
    {
        $this->getJson('/api/tracker/logs?start_date=not-a-date')->assertUnprocessable();
    }

    public function test_series_groups_samples_and_estimates_energy(): void
    {
        Carbon::setTestNow('2026-11-04 04:20:00');

        $this->makeLog(['created_at' => Carbon::parse('2026-11-04 04:00:00'), 'power_output' => 1000]);
        $this->makeLog(['created_at' => Carbon::parse('2026-11-04 04:10:00'), 'power_output' => 3000]);
        $this->makeLog(['created_at' => Carbon::parse('2026-11-01 04:00:00'), 'power_output' => 9000]);
        $this->makeLog(['panel_id' => 'panel2', 'created_at' => Carbon::parse('2026-11-04 04:05:00')]);

        $this->getJson('/api/tracker/series?panel_id=panel1&range=24h')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.t', '2026-11-04T04:00:00Z')
            ->assertJsonPath('data.0.samples', 2)
            ->assertJsonPath('data.0.power', 2000)
            ->assertJsonPath('data.0.peak_power', 3000)
            ->assertJsonPath('summary.samples', 2)
            ->assertJsonPath('summary.energy_wh', 333.33);

        Carbon::setTestNow();
    }

    public function test_daily_series_buckets_start_at_local_midnight(): void
    {
        Carbon::setTestNow('2026-11-04 04:20:00');

        // Same local day in Asia/Makassar, but two different UTC days.
        $this->makeLog(['created_at' => Carbon::parse('2026-11-03 17:00:00')]);
        $this->makeLog(['created_at' => Carbon::parse('2026-11-04 03:00:00')]);

        $this->getJson('/api/tracker/series?panel_id=panel1&range=30d')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.t', '2026-11-03T16:00:00Z');

        Carbon::setTestNow();
    }

    public function test_series_is_empty_without_data(): void
    {
        $this->getJson('/api/tracker/series?panel_id=panel1')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('summary.samples', 0);
    }

    public function test_reset_is_a_one_off_command_and_keeps_the_mode(): void
    {
        $this->postJson('/api/tracker/mode', ['mode' => 'Manual', 'panel_id' => 'panel1'])->assertOk();
        $this->postJson('/api/tracker/mode', ['mode' => 'Reset', 'panel_id' => 'panel1'])->assertOk();

        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1'])
            ->assertCreated()
            ->assertJsonPath('mode', 'Manual')
            ->assertJsonPath('command', 'Reset');

        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1'])
            ->assertCreated()
            ->assertJsonPath('mode', 'Manual')
            ->assertJsonPath('command', null);
    }

    public function test_store_keeps_device_mode_until_one_is_chosen_on_the_dashboard(): void
    {
        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1', 'tracking_mode' => 'Manual'])
            ->assertCreated()
            ->assertJsonPath('data.tracking_mode', 'Manual');

        $this->postJson('/api/tracker/mode', ['mode' => 'Automatic', 'panel_id' => 'panel1'])->assertOk();

        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1', 'tracking_mode' => 'Manual'])
            ->assertCreated()
            ->assertJsonPath('data.tracking_mode', 'Automatic');
    }

    public function test_a_stale_reset_mode_in_cache_is_ignored(): void
    {
        Cache::put('tracking_mode_panel1', 'Reset');

        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1'])
            ->assertCreated()
            ->assertJsonPath('mode', 'Automatic');
    }
}
