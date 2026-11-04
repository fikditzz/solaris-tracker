<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureDevToolsEnabled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// Sengaja tidak memanggil endpoint reset asli agar tes tidak bisa menghapus data.
class DevToolsGuardTest extends TestCase
{
    public function test_seed_and_reset_routes_use_the_guard(): void
    {
        foreach ([['POST', '/api/tracker/reset'], ['GET', '/api/tracker/seed']] as [$method, $uri]) {
            $route = Route::getRoutes()->match(Request::create($uri, $method));

            $this->assertContains(EnsureDevToolsEnabled::class, $route->gatherMiddleware(), $uri);
        }
    }

    public function test_guard_blocks_when_disabled_and_allows_when_enabled(): void
    {
        Route::get('/_guard-probe', fn () => response()->json(['ok' => true]))
            ->middleware(EnsureDevToolsEnabled::class);

        config(['tracker.dev_tools' => false]);
        $this->getJson('/_guard-probe')->assertForbidden()->assertJson(['success' => false]);

        config(['tracker.dev_tools' => true]);
        $this->getJson('/_guard-probe')->assertOk();
    }
}
