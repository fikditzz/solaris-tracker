<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('observed_at')->unique();
            $table->float('temperature')->nullable();
            $table->float('humidity')->nullable();
            $table->float('cloud_cover')->nullable();
            $table->float('precipitation')->nullable();
            $table->float('wind_speed')->nullable();
            $table->float('solar_radiation')->nullable();
            $table->unsignedSmallInteger('weather_code')->nullable();
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_logs');
    }
};
