<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackerLogController;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/tracker/log', [TrackerLogController::class, 'store']);
Route::get('/tracker/latest', [TrackerLogController::class, 'latest']);
Route::post('/tracker/mode', [TrackerLogController::class, 'setMode']);
Route::get('/tracker/logs', [TrackerLogController::class, 'logs']);
Route::post('/tracker/command', [TrackerLogController::class, 'sendCommand']);
Route::post('/tracker/reset', [TrackerLogController::class, 'resetData']);

Route::get('/tracker/seed', function (Request $request) {
    $panelId = $request->query('panel_id', 'panel1');
    $panelsToSeed = $panelId === 'all' ? ['panel1', 'panel2'] : [$panelId];
    
    foreach ($panelsToSeed as $pid) {
        for ($i=0; $i<50; $i++) {
            \App\Models\TrackerLog::create([
                'panel_id' => $pid,
                'azimuth' => rand(0, 360),
                'elevation' => rand(0, 180),
                'ldr_nw' => rand(100, 1000),
                'ldr_ne' => rand(100, 1000),
                'ldr_sw' => rand(100, 1000),
                'ldr_se' => rand(100, 1000),
                'tracking_mode' => rand(0, 1) ? 'Automatic' : 'Manual',
                'manual_command' => rand(0, 1) ? 'Right' : null,
                'created_at' => now()->subMinutes(rand(1, 43200)),
                'updated_at' => now()->subMinutes(rand(1, 43200))
            ]);
        }
    }
    return response()->json(['message' => 'Random records seeded successfully for ' . $panelId]);
});
