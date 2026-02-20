<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add brand_id to products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignUuid('brand_id')
                ->nullable()
                ->after('category_id')
                ->constrained('brands')
                ->nullOnDelete();
        });

        // Product Variation Templates (e.g. "Color", "Size")
        Schema::create('variation_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Color"
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
        });

        // Variation Value Templates (e.g. "Red", "Blue", "S", "M", "L")
        Schema::create('variation_value_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('variation_template_id')->constrained('variation_templates')->cascadeOnDelete();
            $table->string('name'); // e.g. "Red"
            $table->timestamps();

            $table->unique(['variation_template_id', 'name']);
        });

        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 3); // ISO 4217: USD, EUR, LKR, etc.
            $table->string('name');
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 20, 8)->default(1); // relative to base currency
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        // Selling Price Groups (named price tiers, e.g. "Wholesale", "VIP")
        Schema::create('selling_price_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
        });

        // Selling Price Group Prices (per product/variant price in a group)
        Schema::create('selling_price_group_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('selling_price_group_id')->constrained('selling_price_groups')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('price', 20, 8);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->unique(['selling_price_group_id', 'product_id', 'product_variant_id'], 'spg_product_unique');
        });

        // Reference Counts (auto-incrementing ref numbers per type per location)
        Schema::create('reference_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('ref_type'); // purchase, sell, sell_return, expense, stock_adjustment
            $table->foreignUuid('business_location_id')->nullable()->constrained('business_locations')->nullOnDelete();
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'ref_type', 'business_location_id'], 'ref_count_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_counts');
        Schema::dropIfExists('selling_price_group_prices');
        Schema::dropIfExists('selling_price_groups');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('variation_value_templates');
        Schema::dropIfExists('variation_templates');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
    }
};
