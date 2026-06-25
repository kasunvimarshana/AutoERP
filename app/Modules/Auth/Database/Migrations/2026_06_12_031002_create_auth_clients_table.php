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
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('provider_id')->nullable()->constrained('auth_providers', 'id')->nullOnDelete();
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

            $table->unique(['tenant_id', 'client_key'], 'auth_clients_key_uk');
            $table->index(['tenant_id', 'provider_id'], 'auth_clients_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_clients');
    }
};
