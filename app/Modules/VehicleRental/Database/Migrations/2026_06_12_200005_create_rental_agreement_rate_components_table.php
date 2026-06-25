<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreement_rate_components', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('rate_version_id');
            $table->foreignId('vehicle_category_id')->nullable();
            $table->string('component_code', 50);
            $table->string('unit', 30);
            $table->decimal('included_quantity', 20, 6)->default('0.000000');
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('multiplier', 20, 6)->default('1.000000');
            $table->decimal('minimum_amount', 20, 6)->nullable();
            $table->decimal('maximum_amount', 20, 6)->nullable();
            $table->foreignId('tax_group_override_id')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->unsignedInteger('calculation_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['rate_version_id', 'vehicle_category_id', 'component_code'], 'rental_rate_components_version_category_code_uk');
            $table->index(['rate_version_id', 'calculation_order'], 'rental_rate_components_order_idx');

            $table->unique(['id', 'tenant_id'], 'rental_agreement_rate_components_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_agreement_rate_components_organization_un_d3b71abf_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['rate_version_id', 'tenant_id'], 'rental_agreement_rate_components_rate_version_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreement_rate_versions')
                ->cascadeOnDelete();
            $table->foreign(['vehicle_category_id', 'tenant_id'], 'rental_agreement_rate_components_vehicle_categor_bf6bd058_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_categories')
                ->restrictOnDelete();
            $table->foreign(['tax_group_override_id', 'tenant_id'], 'rental_agreement_rate_components_tax_group_overr_36be5cf0_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_agreement_rate_components_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_agreement_rate_components_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_rate_components');
    }
};
