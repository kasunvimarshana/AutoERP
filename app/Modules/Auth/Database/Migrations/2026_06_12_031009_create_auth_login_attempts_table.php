<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('login_identifier', 320)->nullable();
            $table->boolean('was_successful')->default(false);
            $table->string('failure_code', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('attempt_type', 40)->default('password');
            $table->timestamp('attempted_at');

            $table->timestamps();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_login_attempts_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_login_attempts_provider_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_providers')
                ->restrictOnDelete();
            $table->foreign(['client_id', 'tenant_id'], 'auth_login_attempts_client_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_clients')
                ->restrictOnDelete();
            $table->foreign(['identity_id', 'tenant_id'], 'auth_login_attempts_identity_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_identities')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_login_attempts_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->index(['tenant_id', 'login_identifier', 'attempted_at'], 'auth_login_attempts_identifier_idx');
            $table->index(['tenant_id', 'user_id', 'attempted_at'], 'auth_login_attempts_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_login_attempts');
    }
};
