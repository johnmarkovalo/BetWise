<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_conflict_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 50)->unique();
            $table->unsignedInteger('max_concurrent_devices')->default(3);
            $table->unsignedInteger('cooldown_seconds')->default(300);
            $table->unsignedInteger('hourly_limit')->default(50);
            $table->boolean('require_unique_per_team')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE ip_conflict_rules ADD CONSTRAINT chk_ip_rules_max_concurrent CHECK (max_concurrent_devices > 0)');
        DB::statement('ALTER TABLE ip_conflict_rules ADD CONSTRAINT chk_ip_rules_hourly_limit CHECK (hourly_limit > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_conflict_rules');
    }
};
