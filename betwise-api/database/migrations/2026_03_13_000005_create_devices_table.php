<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->nullable();
            $table->string('android_id', 100)->nullable()->unique();
            $table->text('auth_token')->nullable();
            $table->foreignUuid('account_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['online', 'offline', 'error'])->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->unsignedSmallInteger('battery_level')->nullable();
            $table->string('app_version', 20)->nullable();
            $table->timestamps();

            $table->index(['status', 'last_seen'], 'idx_devices_status');
            $table->index('account_id', 'idx_devices_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
