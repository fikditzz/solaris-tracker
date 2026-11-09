<?php

namespace Tests\Feature;

use App\Models\TrackerLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirmwareVersionTest extends TestCase
{
    use RefreshDatabase;

    private function fakeRepo(string $panel1 = '1.2.10', string $panel2 = '1.1.5'): void
    {
        Http::fake([
            '*solar-tracker-2-axis/version.txt' => Http::response($panel1 . "\n"),
            '*solar-tracker-1-axis/version.txt' => Http::response($panel2),
        ]);
    }

    public function test_store_keeps_the_reported_firmware_version(): void
    {
        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1', 'firmware_version' => '1.2.4'])
            ->assertCreated()
            ->assertJsonPath('data.firmware_version', '1.2.4');

        $this->assertDatabaseHas('tracker_logs', ['panel_id' => 'panel1', 'firmware_version' => '1.2.4']);
    }

    public function test_store_still_accepts_logs_without_a_firmware_version(): void
    {
        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1', 'firmware_version' => null])->assertCreated();
        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1'])->assertCreated();
    }

    public function test_store_rejects_an_oversized_firmware_version(): void
    {
        $this->postJson('/api/tracker/log', ['panel_id' => 'panel1', 'firmware_version' => str_repeat('1', 33)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('firmware_version');
    }

    public function test_firmware_reports_each_state(): void
    {
        $this->fakeRepo();
        TrackerLog::create(['panel_id' => 'panel1', 'firmware_version' => '1.2.4']);
        TrackerLog::create(['panel_id' => 'panel2', 'firmware_version' => '1.1.5']);

        $this->getJson('/api/tracker/firmware')
            ->assertOk()
            ->assertJsonPath('data.panel1.installed', '1.2.4')
            ->assertJsonPath('data.panel1.latest', '1.2.10')
            ->assertJsonPath('data.panel1.state', 'update_available')
            ->assertJsonPath('data.panel2.installed', '1.1.5')
            ->assertJsonPath('data.panel2.latest', '1.1.5')
            ->assertJsonPath('data.panel2.state', 'up_to_date')
            ->assertJsonPath('repo_url', 'https://github.com/bengkelit2026-ops/solar-tracker-firmware');
    }

    public function test_firmware_uses_the_newest_log_that_reported_a_version(): void
    {
        $this->fakeRepo();
        TrackerLog::create(['panel_id' => 'panel1', 'firmware_version' => '1.2.4', 'created_at' => now()->subMinutes(10)]);
        TrackerLog::create(['panel_id' => 'panel1', 'firmware_version' => '1.2.10', 'created_at' => now()->subMinutes(5)]);
        TrackerLog::create(['panel_id' => 'panel1', 'created_at' => now()]);

        $this->getJson('/api/tracker/firmware')
            ->assertJsonPath('data.panel1.installed', '1.2.10')
            ->assertJsonPath('data.panel1.state', 'up_to_date');
    }

    public function test_firmware_is_unknown_until_a_device_reports_a_version(): void
    {
        $this->fakeRepo();
        TrackerLog::create(['panel_id' => 'panel1']);

        $this->getJson('/api/tracker/firmware')
            ->assertOk()
            ->assertJsonPath('data.panel1.installed', null)
            ->assertJsonPath('data.panel1.latest', '1.2.10')
            ->assertJsonPath('data.panel1.state', 'unknown')
            ->assertJsonPath('data.panel1.last_seen', null);
    }

    public function test_firmware_survives_the_repo_being_unreachable(): void
    {
        Http::fake(['*' => Http::response('Not Found', 404)]);
        TrackerLog::create(['panel_id' => 'panel1', 'firmware_version' => '1.2.4']);

        $this->getJson('/api/tracker/firmware')
            ->assertOk()
            ->assertJsonPath('data.panel1.installed', '1.2.4')
            ->assertJsonPath('data.panel1.latest', null)
            ->assertJsonPath('data.panel1.state', 'unknown');
    }

    public function test_firmware_ignores_a_version_file_that_is_not_a_version(): void
    {
        Http::fake(['*' => Http::response('<html>rate limited</html>')]);

        $this->getJson('/api/tracker/firmware')
            ->assertJsonPath('data.panel1.latest', null)
            ->assertJsonPath('data.panel2.latest', null);
    }

    public function test_firmware_asks_the_repo_only_once_per_cache_window(): void
    {
        $this->fakeRepo();

        $this->getJson('/api/tracker/firmware')->assertOk();
        $this->getJson('/api/tracker/firmware')->assertOk();

        Http::assertSentCount(2);
    }

    public function test_a_failed_lookup_is_not_retried_on_every_request(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->getJson('/api/tracker/firmware')->assertOk();
        $this->getJson('/api/tracker/firmware')->assertOk();

        Http::assertSentCount(2);
    }
}
