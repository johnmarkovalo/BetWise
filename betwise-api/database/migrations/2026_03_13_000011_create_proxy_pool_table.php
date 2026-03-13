<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_pool', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->ipAddress('ip_address')->unique();
            $table->unsignedSmallInteger('port');
            $table->enum('protocol', ['http', 'https', 'socks4', 'socks5']);
            $table->string('username', 255)->nullable();
            $table->text('password_encrypted')->nullable();
            $table->string('geographic_region', 50)->nullable();
            $table->enum('status', ['active', 'degraded', 'disabled'])->default('active');
            $table->decimal('health_score', 3, 2)->default(1.00);
            $table->unsignedInteger('total_uses')->default(0);
            $table->unsignedInteger('failed_uses')->default(0);
            $table->json('banned_by_providers')->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->timestamps();

            $table->index(['status', 'health_score'], 'idx_proxy_status');
            $table->index(['geographic_region', 'status'], 'idx_proxy_region');
            $table->index('health_score', 'idx_proxy_health');
        });

        DB::statement('ALTER TABLE proxy_pool ADD CONSTRAINT chk_proxy_health_score CHECK (health_score >= 0 AND health_score <= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_pool');
    }
};
