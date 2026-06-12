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
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('provider_id')->nullable()->constrained('auth_providers', 'id')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('auth_clients', 'id')->nullOnDelete();
            $table->foreignId('identity_id')->nullable()->constrained('auth_identities', 'id')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('login_identifier', 320)->nullable();
            $table->boolean('was_successful')->default(false);
            $table->string('failure_code', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('attempt_type', 40)->default('password');
            $table->timestamp('attempted_at');

            $table->timestamps();

            $table->index(['tenant_id', 'login_identifier', 'attempted_at'], 'auth_login_attempts_identifier_idx');
            $table->index(['tenant_id', 'user_id', 'attempted_at'], 'auth_login_attempts_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_login_attempts');
    }
};
