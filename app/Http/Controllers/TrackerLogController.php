<?php

namespace App\Http\Controllers;

use App\Models\TrackerLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackerLogController extends Controller
{
    
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
                'ldr_nw' => 'nullable|numeric',
                'ldr_ne' => 'nullable|numeric',
                'ldr_sw' => 'nullable|numeric',
                'ldr_se' => 'nullable|numeric',
                'motor1_load' => 'nullable|numeric',
                'motor2_load' => 'nullable|numeric',
                'tracking_mode' => 'nullable|string',
                'weather_status' => 'nullable|string',
            ]);

            
            $panelId = $validated['panel_id'] ?? 'panel1';
            $validated['panel_id'] = $panelId;

            
            $mode = \Illuminate\Support\Facades\Cache::get('tracking_mode_' . $panelId, 'Automatic');
            $validated['tracking_mode'] = $mode;

            
            $log = TrackerLog::create($validated);
            
            $command = \Illuminate\Support\Facades\Cache::pull('manual_command_' . $panelId);

            return response()->json([
                'success' => true,
                'message' => 'Data logged successfully',
                'data' => $log,
                'mode' => $mode,
                'command' => $command
            ], 201);
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
        
        if ($panelId === 'all') {
            \Illuminate\Support\Facades\Cache::put('tracking_mode_panel1', $validated['mode']);
            \Illuminate\Support\Facades\Cache::put('tracking_mode_panel2', $validated['mode']);
            
            $latest1 = TrackerLog::where('panel_id', 'panel1')->latest()->first();
            if ($latest1) { $latest1->tracking_mode = $validated['mode']; $latest1->save(); }
            
            $latest2 = TrackerLog::where('panel_id', 'panel2')->latest()->first();
            if ($latest2) { $latest2->tracking_mode = $validated['mode']; $latest2->save(); }
        } else {
            \Illuminate\Support\Facades\Cache::put('tracking_mode_' . $panelId, $validated['mode']);
            
            
            $latest = TrackerLog::where('panel_id', $panelId)->latest()->first();
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
        \Illuminate\Support\Facades\Cache::put('manual_command_' . $panelId, $validated['command'], now()->addMinutes(1));

        return response()->json(['success' => true, 'command' => $validated['command']]);
    }

    public function logs(Request $request)
    {
        $panelId = $request->query('panel_id', 'panel1');
        $query = TrackerLog::where('panel_id', $panelId)->latest();
        
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
            $logs = $query->get();
        } else {
            $logs = $query->take(50)->get();
        }
        return response()->json(['success' => true, 'data' => $logs]);
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
        
        \Illuminate\Support\Facades\Cache::flush();
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
