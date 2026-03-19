<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('round_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('execution_time_ms')->nullable();
            $table->integer('time_drift_ms')->nullable();
            $table->boolean('bet_placed')->nullable();
            $table->unsignedSmallInteger('battery_level')->nullable();
            $table->string('network_type', 20)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['device_id', 'created_at'], 'idx_telemetry_device');
            $table->index('round_id', 'idx_telemetry_round');
            $table->index('error_message', 'idx_telemetry_errors');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry');
    }
};
