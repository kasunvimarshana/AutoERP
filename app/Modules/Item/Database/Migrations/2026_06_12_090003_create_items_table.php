<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'items_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_category_id')->nullable();
            $table->foreignId('item_brand_id')->nullable();
            $table->string('code', 80);
            $table->string('sku', 120)->nullable();
            $table->string('barcode', 120)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('item_type', 30);
            $table->string('tracking_type', 30)->default('none');
            $table->string('costing_method', 30)->default('none');
            $table->foreignId('base_uom_id')->nullable();
            $table->decimal('standard_price', 20, 6)->nullable();
            $table->foreignId('default_tax_group_id')->nullable();
            $table->foreignId('purchase_tax_group_id')->nullable();
            $table->foreignId('sales_tax_group_id')->nullable();
            $table->boolean('is_stockable')->default(false);
            $table->boolean('is_combo')->default(false);
            $table->boolean('is_tax_exempt')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'items_tenant_code_uk');
            $table->unique(['tenant_id', 'sku'], 'items_tenant_sku_uk');
            $table->unique(['tenant_id', 'barcode'], 'items_tenant_barcode_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'items_tenant_org_ix');
            $table->index('sku', 'items_sku_ix');
            $table->index('barcode', 'items_barcode_ix');
            $table->index('item_type', 'items_type_ix');

            $table->unique(['id', 'tenant_id'], 'items_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'items_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_category_id', 'tenant_id'], 'items_item_category_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_categories')
                ->restrictOnDelete();
            $table->foreign(['item_brand_id', 'tenant_id'], 'items_item_brand_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_brands')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'items_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['default_tax_group_id', 'tenant_id'], 'items_default_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();
            $table->foreign(['purchase_tax_group_id', 'tenant_id'], 'items_purchase_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();
            $table->foreign(['sales_tax_group_id', 'tenant_id'], 'items_sales_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
