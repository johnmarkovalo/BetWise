<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('round_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->enum('side', ['banker', 'player', 'tie']);
            $table->decimal('amount', 10, 2);
            $table->enum('outcome', ['win', 'loss', 'tie', 'push'])->nullable();
            $table->decimal('payout', 10, 2)->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['round_id', 'account_id']);

            $table->index('round_id', 'idx_allocations_round');
            $table->index(['account_id', 'created_at'], 'idx_allocations_account');
            $table->index('outcome', 'idx_allocations_outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
