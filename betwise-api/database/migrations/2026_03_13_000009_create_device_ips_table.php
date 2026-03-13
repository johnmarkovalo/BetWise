<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_ips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->constrained()->cascadeOnDelete();
            $table->ipAddress('ip_address');
            $table->enum('ip_type', ['direct', 'proxy', 'vpn']);
            $table->json('proxy_config')->nullable();
            $table->timestamp('active_from')->useCurrent();
            $table->timestamp('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['device_id', 'is_active'], 'idx_device_ips_device');
            $table->index(['ip_address', 'is_active'], 'idx_device_ips_ip');
            $table->index(['is_active', 'active_from'], 'idx_device_ips_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_ips');
    }
};
