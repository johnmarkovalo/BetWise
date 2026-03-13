<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('actor', 100);
            $table->string('action', 100);
            $table->string('entity_type', 50)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->json('payload')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor', 'created_at'], 'idx_audit_actor');
            $table->index(['action', 'created_at'], 'idx_audit_action');
            $table->index(['entity_type', 'entity_id'], 'idx_audit_entity');
            $table->index('created_at', 'idx_audit_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
