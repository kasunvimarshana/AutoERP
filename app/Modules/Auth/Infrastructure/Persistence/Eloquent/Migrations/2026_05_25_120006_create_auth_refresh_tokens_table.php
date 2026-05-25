<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('access_token_id')->nullable()->constrained('auth_access_tokens', 'id')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('auth_clients', 'id')->nullOnDelete();
            $table->foreignId('identity_id')->nullable()->constrained('auth_identities', 'id')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('auth_sessions', 'id')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('refresh_key', 120);
            $table->string('refresh_hash');
            $table->boolean('rotated')->default(false);
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('replaced_by_expires_at')->nullable();
            $table->string('status', 40)->default('active');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'refresh_key'], 'auth_refresh_tokens_key_uk');
            $table->index(['tenant_id', 'session_id', 'status'], 'auth_refresh_tokens_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_refresh_tokens');
    }
};
