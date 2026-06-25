<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_item_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id');
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->string('supplier_item_code')->nullable();
            $table->string('supplier_item_name')->nullable();
            $table->foreignId('default_purchase_uom_id')->nullable();
            $table->decimal('minimum_order_quantity', 20, 6)->default('0.000000');
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_item_mappings_tenant_org_idx');
            $table->index('supplier_id', 'supplier_item_mappings_supplier_idx');
            $table->index('item_id', 'supplier_item_mappings_item_idx');
            $table->index('item_variant_id', 'supplier_item_mappings_variant_idx');
            $table->index('supplier_item_code', 'supplier_item_mappings_code_idx');
            $table->unique(
                ['supplier_id', 'item_id', 'item_variant_id'],
                'supplier_item_mappings_supplier_item_variant_uk',
            );

            $table->unique(['id', 'tenant_id'], 'supplier_item_mappings_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_item_mappings_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_item_mappings_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->cascadeOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'supplier_item_mappings_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'supplier_item_mappings_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['default_purchase_uom_id', 'tenant_id'], 'supplier_item_mappings_default_purchase_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_item_mappings');
    }
};
