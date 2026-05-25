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
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('provider_id')->nullable()->constrained('auth_providers', 'id')->nullOnDelete();
            $table->foreignId('client_id')->constrained('auth_clients', 'id')->cascadeOnDelete();
            $table->foreignId('identity_id')->nullable()->constrained('auth_identities', 'id')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('auth_sessions', 'id')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->nullOnDelete();
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

            $table->unique(['tenant_id', 'code_key'], 'auth_authorization_codes_key_uk');
            $table->index(['tenant_id', 'client_id', 'status'], 'auth_authorization_codes_client_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_authorization_codes');
    }
};
