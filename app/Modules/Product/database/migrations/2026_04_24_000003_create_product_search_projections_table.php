<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_search_projections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'product_search_projections_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id', 'product_search_projections_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'product_search_projections_variant_id_fk')->nullOnDelete();
            $table->unsignedBigInteger('variant_key')->default(0);

            $table->string('product_name');
            $table->string('product_slug');
            $table->string('product_sku')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('variant_sku')->nullable();

            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('brand_name')->nullable();

            $table->unsignedBigInteger('base_uom_id');
            $table->unsignedBigInteger('purchase_uom_id')->nullable();
            $table->unsignedBigInteger('sales_uom_id')->nullable();
            $table->string('base_uom_name')->nullable();
            $table->string('base_uom_symbol')->nullable();
            $table->string('purchase_uom_name')->nullable();
            $table->string('purchase_uom_symbol')->nullable();
            $table->string('sales_uom_name')->nullable();
            $table->string('sales_uom_symbol')->nullable();

            $table->boolean('is_active_product')->default(true);
            $table->boolean('is_active_variant')->default(true);

            $table->text('identifiers_text')->nullable();
            $table->json('identifiers_json')->nullable();
            $table->json('variant_attributes_json')->nullable();
            $table->text('batch_lot_text')->nullable();

            $table->decimal('stock_on_hand', 20, 6)->default(0);
            $table->decimal('stock_reserved', 20, 6)->default(0);
            $table->decimal('stock_available', 20, 6)->default(0);
            $table->json('stock_by_warehouse_json')->nullable();

            $table->decimal('default_sales_unit_price', 20, 6)->nullable();
            $table->unsignedBigInteger('default_sales_currency_id')->nullable();
            $table->unsignedBigInteger('default_sales_price_uom_id')->nullable();
            $table->decimal('default_purchase_unit_price', 20, 6)->nullable();
            $table->unsignedBigInteger('default_purchase_currency_id')->nullable();
            $table->unsignedBigInteger('default_purchase_price_uom_id')->nullable();

            $table->text('searchable_text');
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_projected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'product_id', 'variant_key'], 'product_search_projections_tenant_product_variant_uk');
            $table->index(['tenant_id', 'is_active_product'], 'product_search_projections_tenant_active_idx');
            $table->index(['tenant_id', 'product_name'], 'product_search_projections_tenant_product_name_idx');
            $table->index(['tenant_id', 'product_sku'], 'product_search_projections_tenant_product_sku_idx');
            $table->index(['tenant_id', 'variant_sku'], 'product_search_projections_tenant_variant_sku_idx');
            $table->index(['tenant_id', 'stock_available'], 'product_search_projections_tenant_stock_available_idx');
            $table->index(['tenant_id', 'default_sales_unit_price'], 'product_search_projections_tenant_sales_price_idx');
            $table->index(['tenant_id', 'default_purchase_unit_price'], 'product_search_projections_tenant_purchase_price_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_search_projections');
    }
};
