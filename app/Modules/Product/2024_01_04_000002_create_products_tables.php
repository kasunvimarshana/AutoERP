<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Products ──────────────────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable']);
            $table->unsignedBigInteger('base_uom_id');
            $table->unsignedBigInteger('purchase_uom_id')->nullable();
            $table->unsignedBigInteger('sales_uom_id')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('has_expiry')->default(false);
            $table->decimal('min_stock_level', 18, 4)->nullable();
            $table->decimal('reorder_point', 18, 4)->nullable();
            $table->enum('valuation_method', ['FIFO', 'LIFO', 'FEFO', 'WAC', 'SPECIFIC'])->default('WAC');
            $table->unsignedBigInteger('inventory_account_id')->nullable();  // asset account
            $table->unsignedBigInteger('cogs_account_id')->nullable();       // expense account
            $table->unsignedBigInteger('income_account_id')->nullable();     // revenue account
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('base_uom_id')->references('id')->on('units_of_measure');
            $table->foreign('purchase_uom_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('sales_uom_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('inventory_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('cogs_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('income_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
        });

        // ── Product Attributes (for variable products) ─────────────────────
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);   // e.g. Color, Size, Weight
            $table->enum('type', ['select', 'multiselect', 'text', 'number', 'boolean']);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value');          // e.g. Red, XL, 500mg
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('attribute_id')->references('id')->on('product_attributes')->cascadeOnDelete();
        });

        // ── Product Variants ─────────────────────────────────────────────────
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->string('barcode', 100)->nullable();
            $table->decimal('weight', 10, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['product_id', 'is_active']);
        });

        // ── Variant → Attribute Value mapping ────────────────────────────────
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('attribute_value_id');

            $table->primary(['variant_id', 'attribute_value_id']);
            $table->foreign('variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->foreign('attribute_value_id')->references('id')->on('product_attribute_values')->cascadeOnDelete();
        });

        // ── Combo Items (Bill of Materials lite) ──────────────────────────────
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_product_id');
            $table->unsignedBigInteger('child_product_id');
            $table->unsignedBigInteger('child_variant_id')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->unsignedBigInteger('uom_id');
            $table->timestamps();

            $table->foreign('parent_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('child_product_id')->references('id')->on('products');
            $table->foreign('child_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
        });

        // ── Price Lists ───────────────────────────────────────────────────────
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);
            $table->enum('type', ['sale', 'purchase', 'transfer']);
            $table->unsignedBigInteger('currency_id');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies');
        });

        // ── Product Prices (multi-price, time-bound, tier-based) ──────────────
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('price', 18, 4);
            $table->decimal('min_qty', 18, 4)->default(1); // tier: minimum quantity for this price
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->cascadeOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('currency_id')->references('id')->on('currencies');

            $table->index(['product_id', 'variant_id', 'price_list_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('combo_items');
        Schema::dropIfExists('variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('products');
    }
};
