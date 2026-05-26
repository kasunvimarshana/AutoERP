<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('domain');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable()->index('tenant_domains_verified_by_idx');
            $table->enum('status', ['pending', 'active', 'disabled'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_domains_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_domains_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('tenant_domains_deleted_by_idx');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'domain'], 'tenant_domains_tenant_domain_uk');
            $table->unique('domain', 'tenant_domains_domain_uk');
            $table->index(['tenant_id', 'is_primary'], 'tenant_domains_tenant_primary_idx');
            $table->index(['tenant_id', 'status'], 'tenant_domains_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
