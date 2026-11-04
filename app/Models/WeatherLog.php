<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherLog extends Model
{
    /** A reading older than this no longer describes the current weather. */
    public const STALE_AFTER_MINUTES = 120;

    protected $fillable = [
        'observed_at', 'temperature', 'humidity', 'cloud_cover', 'precipitation',
        'wind_speed', 'solar_radiation', 'weather_code', 'description',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
    ];

    /** Maps a WMO weather code, as returned by Open-Meteo, to a short label. */
    public static function describe(?int $code): string
    {
        return match (true) {
            $code === null => 'Unknown',
            $code === 0 => 'Clear Sky',
            $code === 1, $code === 2 => 'Partly Cloudy',
            $code === 3 => 'Overcast',
            $code === 45, $code === 48 => 'Foggy',
            $code >= 51 && $code <= 67 => 'Rain',
            $code >= 80 && $code <= 82 => 'Showers',
            $code >= 95 => 'Thunderstorm',
            default => 'Unknown',
        };
    }

    public static function latestReading(): ?self
    {
        return static::latest('observed_at')->first();
    }

    public static function currentDescription(): string
    {
        $latest = static::where('observed_at', '>=', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->latest('observed_at')
            ->first();

        return $latest?->description ?? 'Unknown';
    }
}
