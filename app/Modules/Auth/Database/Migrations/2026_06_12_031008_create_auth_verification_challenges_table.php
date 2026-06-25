<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_verification_challenges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('challenge_key', 120);
            $table->string('challenge_type', 60)->default('otp');
            $table->string('channel', 60)->default('email');
            $table->string('target', 320)->nullable();
            $table->string('challenge_hash');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->string('status', 40)->default('pending');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'auth_verification_challenges_id_tenant_uk');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_verification_challenges_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'tenant_id'], 'auth_verification_challenges_provider_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_providers')
                ->cascadeOnDelete();
            $table->foreign(['identity_id', 'tenant_id'], 'auth_verification_challenges_identity_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('auth_identities')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'auth_verification_challenges_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'challenge_key'], 'auth_verification_challenges_key_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'auth_verification_challenges_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_verification_challenges');
    }
};
