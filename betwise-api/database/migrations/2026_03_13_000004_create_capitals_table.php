<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capitals', function (Blueprint $table) {
            $table->uuid('account_id')->primary();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('locked', 15, 2)->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();

            $table->index('balance', 'idx_capitals_balance');
            $table->index('locked', 'idx_capitals_locked');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE capitals ADD CONSTRAINT chk_capitals_balance_non_negative CHECK (balance >= 0)');
            DB::statement('ALTER TABLE capitals ADD CONSTRAINT chk_capitals_locked_non_negative CHECK (locked >= 0)');
            DB::statement('ALTER TABLE capitals ADD CONSTRAINT chk_capitals_balance_gte_locked CHECK (balance >= locked)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('capitals');
    }
};
