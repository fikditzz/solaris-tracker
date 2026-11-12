<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_logs', function (Blueprint $table) {
            // Where the dual-axis firmware is steering the panel; null for older firmware.
            $table->float('target_azimuth')->nullable()->after('elevation');
            $table->float('target_elevation')->nullable()->after('target_azimuth');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_logs', function (Blueprint $table) {
            $table->dropColumn(['target_azimuth', 'target_elevation']);
        });
    }
};
