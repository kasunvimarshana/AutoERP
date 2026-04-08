<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRODUCTS — Central catalog table.
 * Supports: physical/storable, service, digital, combo/bundle, variable/configurable,
 * rental, subscription, raw material, consumable, asset, etc.
 * All behavioral flags inherit from product_type but can be overridden per product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();

            // ── Identity ───────────────────────────────────────────────────
            $table->string('sku', 100)->nullable();           // Stock Keeping Unit
            $table->string('name', 255);
            $table->string('slug', 300)->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('internal_reference', 100)->nullable(); // Internal code
            $table->string('manufacturer_ref', 100)->nullable();

            // ── Classification ─────────────────────────────────────────────
            $table->unsignedBigInteger('product_type_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('attribute_set_id')->nullable();

            // ── Product Type Flags (overrides type defaults) ───────────────
            $table->boolean('is_stockable')->nullable();       // null = inherit from type
            $table->boolean('is_purchasable')->nullable();
            $table->boolean('is_sellable')->nullable();
            $table->boolean('is_rentable')->nullable();
            $table->boolean('is_variable')->default(false);    // Has variants
            $table->boolean('is_kit')->default(false);         // Combo/bundle
            $table->boolean('is_digital')->default(false);
            $table->boolean('is_service')->default(false);

            // ── Tracking ───────────────────────────────────────────────────
            $table->boolean('track_inventory')->default(false);
            $table->boolean('track_serial_numbers')->default(false);
            $table->boolean('track_batches')->default(false);
            $table->boolean('track_lots')->default(false);
            $table->boolean('track_expiry_date')->default(false);
            $table->boolean('track_manufacture_date')->default(false);
            $table->boolean('track_best_before_date')->default(false);
            $table->string('tracking_strategy')->nullable(); // serial|batch|lot|none
            $table->integer('expiry_alert_days')->default(30); // Warn before expiry

            // ── UOM Defaults (overridden per product_uom_settings) ─────────
            $table->unsignedBigInteger('base_uom_id')->nullable();
            $table->unsignedBigInteger('purchase_uom_id')->nullable();
            $table->unsignedBigInteger('sales_uom_id')->nullable();
            $table->unsignedBigInteger('inventory_uom_id')->nullable();

            // ── Pricing ────────────────────────────────────────────────────
            $table->decimal('standard_cost', 19, 6)->default(0);
            $table->decimal('sales_price', 19, 6)->default(0);
            $table->decimal('min_sales_price', 19, 6)->nullable();
            $table->decimal('max_discount_pct', 8, 4)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();

            // ── Tax ────────────────────────────────────────────────────────
            $table->unsignedBigInteger('sales_tax_group_id')->nullable();
            $table->unsignedBigInteger('purchase_tax_group_id')->nullable();
            $table->string('hsn_code', 30)->nullable();   // India GST
            $table->string('sac_code', 30)->nullable();   // Service Accounting Code
            $table->string('hs_code', 30)->nullable();    // International
            $table->string('commodity_code', 30)->nullable();

            // ── Physical Attributes ────────────────────────────────────────
            $table->decimal('weight', 12, 4)->nullable();
            $table->string('weight_unit', 10)->nullable(); // kg, g, lb, oz
            $table->decimal('length', 12, 4)->nullable();
            $table->decimal('width', 12, 4)->nullable();
            $table->decimal('height', 12, 4)->nullable();
            $table->string('dimension_unit', 10)->nullable(); // cm, m, in, ft
            $table->decimal('volume', 12, 4)->nullable();
            $table->string('volume_unit', 10)->nullable();

            // ── Procurement ────────────────────────────────────────────────
            $table->integer('purchase_lead_time_days')->default(0);
            $table->integer('manufacturing_lead_time_days')->default(0);
            $table->decimal('purchase_min_qty', 19, 6)->nullable();
            $table->decimal('purchase_max_qty', 19, 6)->nullable();
            $table->unsignedBigInteger('preferred_vendor_id')->nullable();

            // ── Reorder & Safety Stock ─────────────────────────────────────
            $table->decimal('reorder_point', 19, 6)->nullable();
            $table->decimal('reorder_qty', 19, 6)->nullable();
            $table->decimal('safety_stock_qty', 19, 6)->nullable();
            $table->decimal('max_stock_qty', 19, 6)->nullable();
            $table->decimal('min_stock_qty', 19, 6)->nullable();

            // ── Shelf Life & Storage ───────────────────────────────────────
            $table->integer('shelf_life_days')->nullable();
            $table->string('storage_temperature', 50)->nullable();  // e.g. 2-8°C
            $table->string('storage_conditions', 255)->nullable();  // text: "Cool & Dry"
            $table->boolean('is_hazardous')->default(false);
            $table->string('hazmat_class', 20)->nullable();
            $table->boolean('is_controlled_substance')->default(false);

            // ── Digital Product ────────────────────────────────────────────
            $table->string('digital_file_path')->nullable();
            $table->integer('download_limit')->nullable();
            $table->integer('download_expiry_days')->nullable();

            // ── Rental Product ─────────────────────────────────────────────
            $table->decimal('rental_rate_per_day', 19, 6)->nullable();
            $table->decimal('rental_deposit', 19, 6)->nullable();
            $table->string('rental_period_unit', 20)->nullable(); // hour|day|week|month

            // ── Accounting ─────────────────────────────────────────────────
            $table->unsignedBigInteger('income_account_id')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->unsignedBigInteger('cogs_account_id')->nullable();
            $table->unsignedBigInteger('write_off_account_id')->nullable();

            // ── Valuation (per product override) ───────────────────────────
            $table->string('costing_method')->nullable();
            // fifo | lifo | avco | standard | specific_identification | none

            // ── GS1 / Regulatory ───────────────────────────────────────────
            $table->string('gtin', 50)->nullable();   // GS1 GTIN-8/12/13/14
            $table->string('gs1_company_prefix', 20)->nullable();
            $table->boolean('gs1_enabled')->default(false);
            $table->boolean('requires_fda_registration')->default(false);
            $table->string('fda_registration_number', 50)->nullable();
            $table->string('ndc_code', 30)->nullable(); // Pharma NDC
            $table->string('rx_otc_classification', 20)->nullable(); // Pharma: Rx|OTC

            // ── Status ─────────────────────────────────────────────────────
            $table->string('status', 30)->default('draft');
            // draft | active | discontinued | archived
            $table->boolean('is_active')->default(true);
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();

            // ── Meta / SEO ─────────────────────────────────────────────────
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->json('custom_fields')->nullable();   // Extensible per tenant
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_type_id')->references('id')->on('product_types');
            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('product_brands')->nullOnDelete();
            $table->foreign('attribute_set_id')->references('id')->on('product_attribute_sets')->nullOnDelete();

            $table->index(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'product_type_id']);
            $table->index('gtin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
