<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->enum('role', ['PRIMARY', 'COUNTER']);
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_teams_status');
            $table->index(['role', 'status'], 'idx_teams_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
