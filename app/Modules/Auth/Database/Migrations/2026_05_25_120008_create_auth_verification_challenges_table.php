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
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('provider_id')->nullable()->constrained('auth_providers', 'id')->nullOnDelete();
            $table->foreignId('identity_id')->nullable()->constrained('auth_identities', 'id')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->nullOnDelete();
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

            $table->unique(['tenant_id', 'challenge_key'], 'auth_verification_challenges_key_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'auth_verification_challenges_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_verification_challenges');
    }
};
