<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matchups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 50);
            $table->string('table_id', 100);
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status'], 'idx_matchups_provider');
            $table->index(['table_id', 'status'], 'idx_matchups_table');
            $table->index(['status', 'created_at'], 'idx_matchups_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matchups');
    }
};
