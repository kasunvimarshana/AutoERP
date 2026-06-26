<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->unsignedBigInteger('access_token_id');
            $table->unsignedBigInteger('parent_refresh_token_id')->nullable();
            $table->uuid('family_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('refresh_key', 64);
            $table->string('refresh_digest', 64);
            $table->string('status', 30);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->foreign(['access_token_id', 'tenant_id', 'session_id', 'user_id'], 'auth_refresh_access_fk')
                ->references(['id', 'tenant_id', 'session_id', 'user_id'])->on('auth_access_tokens')->restrictOnDelete();
            $table->foreign(['parent_refresh_token_id', 'tenant_id'], 'auth_refresh_parent_fk')
                ->references(['id', 'tenant_id'])->on('auth_refresh_tokens')->nullOnDelete();
            $table->foreign(['session_id', 'tenant_id', 'user_id'], 'auth_refresh_session_fk')
                ->references(['id', 'tenant_id', 'user_id'])->on('auth_sessions')->restrictOnDelete();
            $table->foreign(['client_id', 'tenant_id'], 'auth_refresh_client_fk')
                ->references(['id', 'tenant_id'])->on('auth_clients')->restrictOnDelete();
            $table->unique('refresh_key', 'auth_refresh_key_uk');
            $table->unique(['id', 'tenant_id'], 'auth_refresh_id_tenant_uk');
            $table->index(['family_id', 'status'], 'auth_refresh_family_idx');
            $table->index(['session_id', 'status', 'expires_at'], 'auth_refresh_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_refresh_tokens');
    }
};
