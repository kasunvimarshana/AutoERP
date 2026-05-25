<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_providers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('provider_key', 120)->comment('Stable provider key, e.g. internal, oidc-google');
            $table->string('name', 160);
            $table->string('guard_name', 100)->default('web');
            $table->string('provider_name', 100)->default('users');
            $table->string('driver', 120)->default('internal');
            $table->string('status', 40)->default('active');
            $table->boolean('is_sso')->default(false);
            $table->json('config')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'provider_key'], 'auth_providers_key_uk');
            $table->index(['tenant_id', 'status'], 'auth_providers_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_providers');
    }
};
