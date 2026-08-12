<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tracker_logs', function (Blueprint $table) {
            $table->id();
            $table->float('irradiance')->default(0);
            $table->float('power_output')->default(0);
            $table->float('voltage')->default(0);
            $table->float('current')->default(0);
            $table->float('battery_percentage')->default(0);
            $table->float('battery_voltage')->default(0);
            $table->float('battery_current')->default(0);
            $table->float('azimuth')->default(0);
            $table->float('elevation')->default(0);
            $table->float('ldr_nw')->default(0);
            $table->float('ldr_ne')->default(0);
            $table->float('ldr_sw')->default(0);
            $table->float('ldr_se')->default(0);
            $table->float('motor1_load')->default(0);
            $table->float('motor2_load')->default(0);
            $table->string('tracking_mode')->default('Automatic');
            $table->string('weather_status')->default('Clear Sky');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_logs');
    }
};
