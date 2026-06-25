<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_platform_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('auth_platform_sessions_public_uk');
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('device_name', 160)->nullable();
            $table->timestamp('authenticated_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['id', 'user_id'], 'auth_platform_sessions_id_user_uk');
            $table->index(['user_id', 'status', 'last_activity_at'], 'auth_platform_sessions_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_platform_sessions');
    }
};
