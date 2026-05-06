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

            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'tenant_domains_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('domain');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'domain'], 'tenant_domains_tenant_domain_uk');
            $table->unique('domain', 'tenant_domains_domain_uk');
            $table->index(['tenant_id', 'is_primary'], 'tenant_domains_tenant_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
