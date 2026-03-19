<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('matchup_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('execute_at');
            $table->enum('status', ['preparing', 'prepared', 'executing', 'completed', 'aborted'])->default('prepared');
            $table->string('seed', 64);
            $table->decimal('total_capital', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['execute_at', 'status'], 'idx_rounds_execute');
            $table->index(['matchup_id', 'status'], 'idx_rounds_matchup');
            $table->index(['status', 'created_at'], 'idx_rounds_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
