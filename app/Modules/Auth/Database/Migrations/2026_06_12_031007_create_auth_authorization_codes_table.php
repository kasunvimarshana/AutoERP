<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_authorization_codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code_key', 120);
            $table->string('code_hash');
            $table->json('scopes')->nullable();
            $table->string('code_challenge', 255)->nullable();
            $table->string('code_challenge_method', 20)->nullable();
            $table->string('redirect_uri', 2048)->nullable();
            $table->string('status', 40)->default('pending');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'auth_authorization_codes_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_authorization_codes_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_authorization_codes_provider_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_providers')
                ->cascadeOnDelete();
            $table->foreign(['client_id', 'tenant_id'], 'auth_authorization_codes_client_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_clients')
                ->cascadeOnDelete();
            $table->foreign(['identity_id', 'tenant_id'], 'auth_authorization_codes_identity_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_identities')
                ->cascadeOnDelete();
            $table->foreign(['session_id', 'tenant_id'], 'auth_authorization_codes_session_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_sessions')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_authorization_codes_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'code_key'], 'auth_authorization_codes_key_uk');
            $table->index(['tenant_id', 'client_id', 'status'], 'auth_authorization_codes_client_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_authorization_codes');
    }
};
