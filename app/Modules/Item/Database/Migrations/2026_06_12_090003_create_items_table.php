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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->foreignId('item_brand_id')->nullable()->constrained('item_brands')->nullOnDelete();
            $table->string('code', 80);
            $table->string('sku', 120)->nullable();
            $table->string('barcode', 120)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('item_type', 30);
            $table->string('tracking_type', 30)->default('none');
            $table->string('costing_method', 30)->default('none');
            $table->foreignId('base_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('standard_price', 20, 6)->nullable();
            $table->foreignId('default_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->foreignId('purchase_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->foreignId('sales_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
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
            $table->index(['tenant_id', 'organization_unit_id'], 'items_tenant_org_idx');
            $table->index('sku', 'items_sku_idx');
            $table->index('barcode', 'items_barcode_idx');
            $table->index('item_type', 'items_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
