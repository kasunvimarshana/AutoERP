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
            $table->uuid('public_id');
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 30);
            $table->string('ip_address', 45);
            $table->string('user_agent', 1024)->nullable();
            $table->string('device_name', 160)->nullable();
            $table->dateTime('authenticated_at');
            $table->dateTime('last_activity_at');
            $table->dateTime('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique('public_id', 'auth_session_public_uk');
            $table->unique(['id', 'tenant_id', 'user_id'], 'auth_session_graph_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_session_ou_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_session_provider_fk')
                ->references(['id', 'tenant_id'])->on('auth_providers')->restrictOnDelete();
            $table->foreign(['identity_id', 'tenant_id'], 'auth_session_identity_fk')
                ->references(['id', 'tenant_id'])->on('auth_identities')->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_session_user_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'user_id', 'status', 'expires_at'], 'auth_session_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
