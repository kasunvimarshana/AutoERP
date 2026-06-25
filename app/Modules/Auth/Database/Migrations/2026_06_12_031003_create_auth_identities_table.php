<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_identities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider_user_key', 190)->comment('Provider-side subject/identifier');
            $table->string('status', 40)->default('active');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('last_authenticated_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('claims')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'auth_identities_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_identities_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_identities_provider_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_providers')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_identities_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'provider_id', 'provider_user_key'], 'auth_identities_subject_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'auth_identities_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_identities');
    }
};
