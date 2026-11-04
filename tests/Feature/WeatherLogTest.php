<?php

namespace Tests\Feature;

use App\Models\TrackerLog;
use App\Models\WeatherLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherLogTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fakeOpenMeteo(string $time = '2026-11-04T04:15', int $code = 3): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response(['current' => [
            'time' => $time,
            'temperature_2m' => 29.4,
            'relative_humidity_2m' => 78,
            'precipitation' => 0.2,
            'cloud_cover' => 90,
            'wind_speed_10m' => 12.5,
            'shortwave_radiation' => 410,
            'weather_code' => $code,
        ]])]);
    }

    public function test_command_stores_the_current_weather_for_the_site(): void
    {
        $this->fakeOpenMeteo();

        $this->artisan('weather:record')->assertSuccessful();

        $log = WeatherLog::sole();
        $this->assertSame('2026-11-04 04:15:00', $log->observed_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('Overcast', $log->description);
        $this->assertSame(3, $log->weather_code);
        $this->assertEquals(29.4, $log->temperature);
        $this->assertEquals(78, $log->humidity);
        $this->assertEquals(410, $log->solar_radiation);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'latitude=-5.2150509')
            && str_contains($request->url(), 'longitude=119.5793433'));
    }

    public function test_repeated_run_in_the_same_interval_does_not_duplicate(): void
    {
        $this->fakeOpenMeteo();

        $this->artisan('weather:record')->assertSuccessful();
        $this->artisan('weather:record')->assertSuccessful();

        $this->assertSame(1, WeatherLog::count());
    }

    public function test_failed_request_stores_nothing_and_reports_failure(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response('unavailable', 503)]);

        $this->artisan('weather:record')->assertFailed();

        $this->assertSame(0, WeatherLog::count());
    }

    public function test_unknown_weather_code_is_not_reported_as_clear(): void
    {
        $this->assertSame('Unknown', WeatherLog::describe(71));
        $this->assertSame('Unknown', WeatherLog::describe(null));
        $this->assertSame('Clear Sky', WeatherLog::describe(0));
    }

    public function test_new_tracker_log_stores_recorded_weather_not_the_device_value(): void
    {
        Carbon::setTestNow('2026-11-04 04:20:00');
        $this->fakeOpenMeteo();
        $this->artisan('weather:record')->assertSuccessful();

        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1', 'weather_status' => 'Clear Sky'])
            ->assertCreated()
            ->assertJsonPath('data.weather_status', 'Overcast');
    }

    public function test_tracker_log_is_unknown_when_recorded_weather_is_stale(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        $this->fakeOpenMeteo('2026-11-04T04:15');
        $this->artisan('weather:record')->assertSuccessful();

        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1'])
            ->assertCreated()
            ->assertJsonPath('data.weather_status', 'Unknown');
    }

    public function test_latest_endpoint_flags_stale_and_empty_data(): void
    {
        $this->getJson('/api/tracker/weather/latest')
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('stale', true);

        Carbon::setTestNow('2026-11-04 04:20:00');
        $this->fakeOpenMeteo();
        $this->artisan('weather:record')->assertSuccessful();

        $this->getJson('/api/tracker/weather/latest')
            ->assertOk()
            ->assertJsonPath('data.description', 'Overcast')
            ->assertJsonPath('stale', false);

        Carbon::setTestNow('2026-11-04 12:00:00');
        $this->getJson('/api/tracker/weather/latest')->assertJsonPath('stale', true);
    }

    public function test_history_endpoint_returns_only_the_requested_range_in_order(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');
        foreach (['2026-11-04 10:00:00', '2026-11-04 08:00:00', '2026-10-20 08:00:00'] as $time) {
            WeatherLog::create(['observed_at' => Carbon::parse($time), 'description' => 'Rain', 'temperature' => 27]);
        }

        $rows = $this->getJson('/api/tracker/weather?range=24h')->assertOk()->assertJsonCount(2, 'data')->json('data');
        $this->assertLessThan($rows[1]['observed_at'], $rows[0]['observed_at']);

        $this->getJson('/api/tracker/weather?range=30d')->assertJsonCount(3, 'data');
        $this->getJson('/api/tracker/weather?range=bad')->assertUnprocessable();
    }

    public function test_schedule_records_weather_every_fifteen_minutes(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('weather:record')
            ->assertSuccessful();
    }

    public function test_tracker_logs_are_unaffected_when_no_weather_was_recorded(): void
    {
        $this->postJson('/api/tracker/log', ['panel_id' => 'panel2', 'voltage' => 12])->assertCreated();

        $this->assertSame('Unknown', TrackerLog::sole()->weather_status);
    }
}
