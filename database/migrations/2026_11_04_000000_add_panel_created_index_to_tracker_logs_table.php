<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_logs', function (Blueprint $table) {
            $table->index(['panel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tracker_logs', function (Blueprint $table) {
            $table->dropIndex(['panel_id', 'created_at']);
        });
    }
};
