<?php

namespace App\Console\Commands;

use App\Models\WeatherLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecordWeather extends Command
{
    protected $signature = 'weather:record';

    protected $description = 'Fetch the current weather at the panel site from Open-Meteo and store it';

    private const API_URL = 'https://api.open-meteo.com/v1/forecast';

    private const FIELDS = 'temperature_2m,relative_humidity_2m,precipitation,cloud_cover,wind_speed_10m,shortwave_radiation,weather_code';

    public function handle(): int
    {
        try {
            $current = Http::timeout(15)
                ->retry(2, 2000)
                ->get(self::API_URL, [
                    'latitude' => config('tracker.site.latitude'),
                    'longitude' => config('tracker.site.longitude'),
                    'current' => self::FIELDS,
                ])
                ->throw()
                ->json('current');

            if (empty($current['time'])) {
                throw new \RuntimeException('Open-Meteo returned no current weather.');
            }

            // Open-Meteo reports GMT by default. Its value only changes every 15 minutes,
            // so keying on it keeps a repeated run from storing a duplicate row.
            $code = isset($current['weather_code']) ? (int) $current['weather_code'] : null;
            $log = WeatherLog::updateOrCreate(
                ['observed_at' => Carbon::parse($current['time'], 'UTC')],
                [
                    'temperature' => $current['temperature_2m'] ?? null,
                    'humidity' => $current['relative_humidity_2m'] ?? null,
                    'cloud_cover' => $current['cloud_cover'] ?? null,
                    'precipitation' => $current['precipitation'] ?? null,
                    'wind_speed' => $current['wind_speed_10m'] ?? null,
                    'solar_radiation' => $current['shortwave_radiation'] ?? null,
                    'weather_code' => $code,
                    'description' => WeatherLog::describe($code),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Could not record the weather: ' . $e->getMessage());
            $this->error('Could not record the weather: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Recorded {$log->description}, {$log->temperature} C at {$log->observed_at}");

        return self::SUCCESS;
    }
}
