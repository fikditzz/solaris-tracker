<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'irradiance', 'power_output', 'voltage', 'current', 
        'battery_percentage', 'battery_voltage', 'battery_current', 
        'azimuth', 'elevation', 
        'ldr_nw', 'ldr_ne', 'ldr_sw', 'ldr_se',
        'motor1_load', 'motor2_load',
        'tracking_mode', 'weather_status', 'created_at'
    ];
}
