<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('provider_id')->nullable();
            $table->string('client_key', 120);
            $table->string('client_name', 180);
            $table->string('client_secret_hash')->nullable();
            $table->string('status', 40)->default('active');
            $table->json('allowed_grant_types')->nullable();
            $table->json('allowed_scopes')->nullable();
            $table->json('redirect_uris')->nullable();
            $table->json('trusted_origins')->nullable();
            $table->boolean('is_confidential')->default(true);
            $table->boolean('is_first_party')->default(false);
            $table->timestamp('secret_rotated_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'auth_clients_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_clients_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_clients_provider_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_providers')
                ->restrictOnDelete();
            $table->unique(['tenant_id', 'client_key'], 'auth_clients_key_uk');
            $table->index(['tenant_id', 'provider_id'], 'auth_clients_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_clients');
    }
};
