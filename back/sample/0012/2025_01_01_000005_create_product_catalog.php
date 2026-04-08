<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Units of Measure ─────────────────────────────────────────────────
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('abbreviation');
            $table->string('type');  // weight|volume|length|area|count|time|digital
            $table->decimal('base_factor', 20, 10)->default(1);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Tax Classes ──────────────────────────────────────────────────────
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->decimal('rate', 8, 4)->default(0);
            $table->string('type')->default('percentage');  // percentage|fixed
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Categories (hierarchical) ────────────────────────────────────────
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('depth')->default(0);
            $table->string('path')->nullable();  // materialized path
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Attribute Groups (Size, Color, Material…) ────────────────────────
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('display_type')->default('dropdown');
            // dropdown|radio|color_swatch|button|image|text
            $table->boolean('is_variant_generator')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_group_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('display_value')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Products ─────────────────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('tax_class_id')->nullable()->index();
            $table->unsignedBigInteger('uom_id')->nullable()->index();

            // ── Identity ───────────────────────────────────────────────────
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('barcode_type', 20)->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->json('images')->nullable();

            // ── Type (ProductType value object) ────────────────────────────
            $table->string('type', 30)->default('physical');
            // physical|service|digital|subscription|combo|variable|
            // raw_material|finished_good|wip|kit

            // ── Flags ──────────────────────────────────────────────────────
            $table->boolean('is_variable')->default(false);
            $table->boolean('is_composite')->default(false);
            $table->boolean('is_kit')->default(false);
            $table->boolean('is_stockable')->default(true);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batches')->default(false);
            $table->boolean('track_lots')->default(false);
            $table->boolean('track_serials')->default(false);
            $table->boolean('track_expiry')->default(false);

            // ── Shelf life ─────────────────────────────────────────────────
            $table->integer('shelf_life_days')->nullable();
            $table->integer('expiry_warning_days')->nullable();

            // ── Valuation (per-product override) ──────────────────────────
            $table->string('valuation_method', 30)->nullable();
            $table->decimal('standard_cost', 20, 6)->nullable();
            $table->decimal('standard_price', 20, 4)->nullable();

            // ── UoM (JSON array — confirmed from PR #37) ───────────────────
            // [{"unit":"box","type":"buying","conversion_factor":12},
            //  {"unit":"pcs","type":"selling","conversion_factor":1},
            //  {"unit":"pcs","type":"inventory","conversion_factor":1}]
            $table->json('units_of_measure')->nullable();

            // ── Physical attributes ────────────────────────────────────────
            $table->decimal('weight', 12, 4)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->decimal('length', 10, 4)->nullable();
            $table->decimal('width', 10, 4)->nullable();
            $table->decimal('height', 10, 4)->nullable();
            $table->string('dimension_unit', 10)->nullable();

            // ── Reorder ────────────────────────────────────────────────────
            $table->decimal('reorder_point', 14, 4)->nullable();
            $table->decimal('reorder_quantity', 14, 4)->nullable();
            $table->decimal('min_stock_level', 14, 4)->nullable();
            $table->decimal('max_stock_level', 14, 4)->nullable();
            $table->decimal('safety_stock', 14, 4)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->decimal('economic_order_qty', 14, 4)->nullable();

            // ── Digital product ────────────────────────────────────────────
            $table->string('download_url')->nullable();
            $table->integer('download_limit')->nullable();
            $table->integer('download_expiry_days')->nullable();
            $table->string('license_type')->nullable();

            // ── Subscription ───────────────────────────────────────────────
            $table->string('subscription_interval')->nullable();
            $table->integer('subscription_interval_count')->nullable();
            $table->integer('subscription_trial_days')->nullable();

            // ── International trade ────────────────────────────────────────
            $table->string('hs_code')->nullable();
            $table->string('country_of_origin', 2)->nullable();

            // ── Status & meta ──────────────────────────────────────────────
            $table->string('status')->default('active');
            // draft|active|inactive|archived|discontinued
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('seo')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
        });

        // ── Product Variants ─────────────────────────────────────────────────
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->json('images')->nullable();
            $table->json('units_of_measure')->nullable();  // per-variant UoM override
            $table->decimal('cost_price', 20, 6)->nullable();
            $table->decimal('selling_price', 20, 4)->nullable();
            $table->decimal('compare_at_price', 20, 4)->nullable();
            $table->decimal('wholesale_price', 20, 4)->nullable();
            $table->decimal('weight', 12, 4)->nullable();
            $table->decimal('length', 10, 4)->nullable();
            $table->decimal('width', 10, 4)->nullable();
            $table->decimal('height', 10, 4)->nullable();
            $table->string('valuation_method', 30)->nullable();
            $table->decimal('standard_cost', 20, 6)->nullable();
            $table->decimal('reorder_point', 14, 4)->nullable();
            $table->decimal('min_stock_level', 14, 4)->nullable();
            $table->decimal('max_stock_level', 14, 4)->nullable();
            $table->boolean('track_batches')->nullable();
            $table->boolean('track_serials')->nullable();
            $table->boolean('track_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
        });

        // ── Variant ↔ Attribute pivot ────────────────────────────────────────
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'attribute_group_id'], 'vav_unique');
        });

        // ── Bundle / Combo / Kit components ─────────────────────────────────
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_product_id')->index();
            $table->unsignedBigInteger('component_product_id')->index();
            $table->unsignedBigInteger('component_variant_id')->nullable()->index();
            $table->decimal('quantity', 14, 4)->default(1);
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->decimal('optional_price_override', 20, 4)->nullable();
            $table->boolean('deduct_from_stock_separately')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Price Lists ──────────────────────────────────────────────────────
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('currency', 3)->default('USD');
            $table->string('type')->default('selling');
            // selling|purchase|wholesale|retail|special
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->decimal('price', 20, 4);
            $table->decimal('min_quantity', 14, 4)->default(1);
            $table->string('discount_type')->nullable();  // percentage|fixed
            $table->decimal('discount_value', 10, 4)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('product_components');
        Schema::dropIfExists('variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_groups');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('tax_classes');
        Schema::dropIfExists('units_of_measure');
    }
};
