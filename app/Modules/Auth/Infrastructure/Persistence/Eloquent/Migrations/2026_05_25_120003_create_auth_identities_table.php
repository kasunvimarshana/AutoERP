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
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('provider_id')->constrained('auth_providers', 'id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('provider_user_key', 190)->comment('Provider-side subject/identifier');
            $table->string('status', 40)->default('active');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('last_authenticated_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('claims')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'provider_id', 'provider_user_key'], 'auth_identities_subject_uk');
            $table->index(['tenant_id', 'user_id', 'status'], 'auth_identities_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_identities');
    }
};
