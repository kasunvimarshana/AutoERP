<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_primary_domains', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->primary('tenant_primary_domains_tenant_pk');
            $table->unsignedBigInteger('tenant_domain_id')->unique('tenant_primary_domains_domain_uk');
            $table->unsignedBigInteger('row_version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_primary_domains_updated_by_ix');
            $table->timestamps();

            $table->foreign('tenant_id', 'tenant_primary_domains_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreign(
                ['tenant_domain_id', 'tenant_id'],
                'tenant_primary_domains_domain_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('tenant_domains')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_primary_domains');
    }
};
