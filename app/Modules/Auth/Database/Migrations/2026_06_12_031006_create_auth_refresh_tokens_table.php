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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('access_token_id');
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('refresh_key', 120);
            $table->string('refresh_hash');
            $table->boolean('rotated')->default(false);
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('replaced_by_expires_at')->nullable();
            $table->string('status', 40)->default('active');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'auth_refresh_tokens_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_refresh_tokens_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['access_token_id', 'tenant_id'], 'auth_refresh_tokens_access_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_access_tokens')
                ->cascadeOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_refresh_tokens_provider_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_providers')
                ->cascadeOnDelete();
            $table->foreign(['client_id', 'tenant_id'], 'auth_refresh_tokens_client_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_clients')
                ->cascadeOnDelete();
            $table->foreign(['identity_id', 'tenant_id'], 'auth_refresh_tokens_identity_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_identities')
                ->cascadeOnDelete();
            $table->foreign(['session_id', 'tenant_id'], 'auth_refresh_tokens_session_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_sessions')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_refresh_tokens_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique('refresh_key', 'auth_refresh_tokens_key_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'auth_refresh_tokens_user_idx');
            $table->index(['tenant_id', 'session_id', 'status'], 'auth_refresh_tokens_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_refresh_tokens');
    }
};
