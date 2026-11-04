<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackerLog extends Model
{
    use HasFactory;

    public const STATUS_LOCKED = 'Sun Locked';
    public const STATUS_TRACKING = 'Tracking...';
    public const STATUS_MANUAL = 'Manual Override';
    public const STATUS_STOPPED = 'Stopped';

    public const STATUSES = [
        self::STATUS_LOCKED,
        self::STATUS_TRACKING,
        self::STATUS_MANUAL,
        self::STATUS_STOPPED,
    ];

    private const LDR_LOCK_MARGIN_PERCENT = 15;
    private const SINGLE_AXIS_LOCK_DEGREES = 2;

    protected $fillable = [
        'panel_id',
        'irradiance', 'power_output', 'voltage', 'current',
        'battery_percentage', 'battery_voltage', 'battery_current',
        'azimuth', 'elevation',
        'ldr_nw', 'ldr_ne', 'ldr_sw', 'ldr_se',
        'motor1_load', 'motor2_load',
        'tracking_mode', 'weather_status', 'created_at'
    ];

    protected $appends = ['status'];

    public function getStatusAttribute(): string
    {
        if ($this->tracking_mode === 'Manual') {
            return self::STATUS_MANUAL;
        }
        if ($this->tracking_mode === 'Stop') {
            return self::STATUS_STOPPED;
        }

        return $this->isLocked() ? self::STATUS_LOCKED : self::STATUS_TRACKING;
    }

    private function isLocked(): bool
    {
        if ($this->panel_id !== 'panel1') {
            return abs(($this->azimuth ?? 0) - ($this->elevation ?? 0)) <= self::SINGLE_AXIS_LOCK_DEGREES;
        }

        $nw = $this->ldr_nw ?? 0;
        $ne = $this->ldr_ne ?? 0;
        $sw = $this->ldr_sw ?? 0;
        $se = $this->ldr_se ?? 0;
        $average = ($nw + $ne + $sw + $se) / 4;

        if ($average <= 0) {
            return false;
        }

        $maxDiff = max(abs($nw - $ne), abs($sw - $se), abs($nw - $sw), abs($ne - $se));

        return ($maxDiff / $average) * 100 <= self::LDR_LOCK_MARGIN_PERCENT;
    }

    /**
     * SQL counterpart of getStatusAttribute(), so a status filter works across every page of logs.
     */
    public function scopeWhereStatus(Builder $query, string $status, string $panelId): Builder
    {
        if ($status === self::STATUS_MANUAL) {
            return $query->where('tracking_mode', 'Manual');
        }
        if ($status === self::STATUS_STOPPED) {
            return $query->where('tracking_mode', 'Stop');
        }

        $query->whereNotIn('tracking_mode', ['Manual', 'Stop']);
        $locked = $panelId === 'panel1' ? $this->ldrLockedSql() : 'ABS(azimuth - elevation) <= '.self::SINGLE_AXIS_LOCK_DEGREES;

        return $status === self::STATUS_LOCKED
            ? $query->whereRaw($locked)
            : $query->whereRaw("NOT ($locked)");
    }

    private function ldrLockedSql(): string
    {
        $average = '(ldr_nw + ldr_ne + ldr_sw + ldr_se) / 4';
        $limit = '('.self::LDR_LOCK_MARGIN_PERCENT." * $average)";

        return "($average > 0"
            ." AND ABS(ldr_nw - ldr_ne) * 100 <= $limit"
            ." AND ABS(ldr_sw - ldr_se) * 100 <= $limit"
            ." AND ABS(ldr_nw - ldr_sw) * 100 <= $limit"
            ." AND ABS(ldr_ne - ldr_se) * 100 <= $limit)";
    }
}
