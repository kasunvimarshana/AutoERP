<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'uom_conversions_tenant_fk')->restrictOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('from_uom_id');
            $table->foreignId('to_uom_id');
            $table->decimal('conversion_factor', 20, 6);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'from_uom_id', 'to_uom_id'],
                'uom_conversions_from_to_uk'
            );
            $table->index('tenant_id', 'uom_conversions_tenant_ix');
            $table->index('organization_unit_id', 'uom_conversions_org_ix');
            $table->index('from_uom_id', 'uom_conversions_from_uom_ix');
            $table->index('to_uom_id', 'uom_conversions_to_uom_ix');
            $table->index(['tenant_id', 'is_active'], 'uom_conversions_active_ix');

            $table->unique(['id', 'tenant_id'], 'uom_conversions_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'uom_conversions_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['from_uom_id', 'tenant_id'], 'uom_conversions_from_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['to_uom_id', 'tenant_id'], 'uom_conversions_to_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
