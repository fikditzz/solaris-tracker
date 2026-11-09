<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_logs', function (Blueprint $table) {
            $table->string('firmware_version', 32)->nullable()->after('weather_status');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_logs', function (Blueprint $table) {
            $table->dropColumn('firmware_version');
        });
    }
};
