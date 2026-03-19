<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_ip_id')->constrained('device_ips')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->foreignUuid('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('round_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('used_at')->useCurrent();
            $table->boolean('success')->nullable();
            $table->boolean('flagged')->default(false);

            $table->index(['device_ip_id', 'used_at'], 'idx_ip_usage_device_ip');
            $table->index(['provider', 'used_at'], 'idx_ip_usage_provider');
            $table->index(['flagged', 'used_at'], 'idx_ip_usage_flagged');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_usage_logs');
    }
};
