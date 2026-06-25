<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('session_key', 120);
            $table->string('status', 40)->default('active');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('device_name', 160)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'auth_sessions_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_sessions_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_sessions_provider_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_providers')
                ->restrictOnDelete();
            $table->foreign(['identity_id', 'tenant_id'], 'auth_sessions_identity_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_identities')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_sessions_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'session_key'], 'auth_sessions_key_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'auth_sessions_user_status_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
