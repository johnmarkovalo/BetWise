<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 50);
            $table->decimal('commission_pct', 5, 2);
            $table->foreignUuid('team_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
            $table->decimal('min_balance_threshold', 10, 2)->default(100.00);
            $table->timestamps();

            $table->index(['team_id', 'status'], 'idx_accounts_team');
            $table->index(['provider', 'status'], 'idx_accounts_provider');
            $table->index('commission_pct', 'idx_accounts_commission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
