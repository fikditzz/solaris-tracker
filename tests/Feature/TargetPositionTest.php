<?php

namespace Tests\Feature;

use App\Models\TrackerLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetPositionTest extends TestCase
{
    use RefreshDatabase;

    private function dualAxisPayload(array $overrides = []): array
    {
        // Same shape mqtt_bridge.py posts for a solartracker/2axis/data message.
        return array_merge([
            'panel_id' => 'panel1',
            'azimuth' => 121.5,
            'elevation' => 63.25,
            'target_azimuth' => 123.0,
            'target_elevation' => 64.0,
            'voltage' => 18.42,
            'current' => 1.37,
            'power_output' => 25.23,
            'ldr_nw' => 812,
            'ldr_ne' => 790,
            'ldr_sw' => 798,
            'ldr_se' => 805,
            'tracking_mode' => 'Automatic',
            'firmware_version' => '1.2.10',
        ], $overrides);
    }

    public function test_store_keeps_the_dual_axis_target(): void
    {
        $this->postJson('/api/tracker/log', $this->dualAxisPayload())
            ->assertCreated()
            ->assertJsonPath('data.target_azimuth', 123)
            ->assertJsonPath('data.target_elevation', 64);

        $log = TrackerLog::firstOrFail();
        $this->assertEqualsWithDelta(123.0, $log->target_azimuth, 0.001);
        $this->assertEqualsWithDelta(64.0, $log->target_elevation, 0.001);
        $this->assertEqualsWithDelta(121.5, $log->azimuth, 0.001);
    }

    public function test_latest_and_logs_return_the_target(): void
    {
        $this->postJson('/api/tracker/log', $this->dualAxisPayload())->assertCreated();

        $this->getJson('/api/tracker/latest?panel_id=panel1')
            ->assertJsonPath('data.target_azimuth', 123)
            ->assertJsonPath('data.target_elevation', 64);

        $this->getJson('/api/tracker/logs?panel_id=panel1')
            ->assertJsonPath('data.0.target_azimuth', 123)
            ->assertJsonPath('data.0.target_elevation', 64);
    }

    public function test_older_firmware_without_a_target_still_logs(): void
    {
        $payload = $this->dualAxisPayload(['target_azimuth' => null, 'target_elevation' => null]);
        $this->postJson('/api/tracker/log', $payload)->assertCreated();

        unset($payload['target_azimuth'], $payload['target_elevation']);
        $this->postJson('/api/tracker/log', $payload)->assertCreated();

        $this->assertSame(2, TrackerLog::whereNull('target_azimuth')->whereNull('target_elevation')->count());
    }

    public function test_a_target_that_is_not_a_number_is_rejected(): void
    {
        $this->postJson('/api/tracker/log', $this->dualAxisPayload(['target_azimuth' => 'north']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('target_azimuth');
    }

    public function test_the_target_does_not_change_how_sun_locked_is_decided(): void
    {
        // Balanced light sensors keep the LDR rule saying "locked", even far from the target.
        $this->postJson('/api/tracker/log', $this->dualAxisPayload(['target_azimuth' => 300, 'target_elevation' => 10]))
            ->assertCreated()
            ->assertJsonPath('data.status', TrackerLog::STATUS_LOCKED);
    }
}
