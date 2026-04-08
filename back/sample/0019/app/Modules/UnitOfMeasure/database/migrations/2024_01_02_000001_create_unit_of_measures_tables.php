<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit of Measure Module
 * Supports: multi-UOM per product (buying, selling, inventory, base),
 * UOM categories, bidirectional conversion, GS1 compatibility.
 * Industry examples:
 *   Pharma: mg → tablet → blister → box → carton
 *   Grocery: g → kg → bag → pallet
 *   Retail: piece → dozen → box → pallet
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── UOM Categories ──────────────────────────────────────────────────
        Schema::create('uom_categories', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('measure_type', 30)->default('unit');
            // unit | weight | volume | length | area | time | temperature | energy | digital | custom
            $table->boolean('is_system')->default(false);  // System-managed, not deletable
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        // ── Units of Measure ────────────────────────────────────────────────
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('uom_category_id');

            $table->string('code', 50);           // Internal code: kg, g, pcs, doz
            $table->string('name', 100);          // Display name: Kilogram
            $table->string('symbol', 20);         // kg, g, pc, doz, ml, L
            $table->string('plural_name', 100)->nullable();

            // Reference / base relationship within category
            $table->boolean('is_base_unit')->default(false);  // The reference UOM in category
            $table->decimal('conversion_factor', 24, 10)->default(1.0000000000);
            // Qty of THIS unit = 1 × base_unit × factor
            // e.g. if base=g: kg factor=1000, mg factor=0.001
            $table->string('rounding_precision', 20)->default('0.01');

            // GS1 Unit of Measure codes (UN/ECE Recommendation 20)
            $table->string('gs1_uom_code', 10)->nullable();   // e.g. KGM, MTR, LTR, PCE
            $table->string('unece_code', 10)->nullable();      // UN/CEFACT unit code

            // Behavior flags
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_inventory_unit')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('uom_category_id')->references('id')->on('uom_categories');
            $table->unique(['tenant_id', 'code']);
        });

        // ── UOM Conversions ─────────────────────────────────────────────────
        // Cross-category or within-category explicit conversions.
        // Bidirectional: from_uom → to_uom AND to_uom → from_uom
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->decimal('factor', 24, 10);        // from_qty × factor = to_qty
            $table->decimal('offset', 24, 10)->default(0); // For non-ratio conversions (°C→°F)
            $table->string('formula', 100)->nullable(); // Custom formula string
            $table->string('conversion_type', 30)->default('multiply');
            // multiply | divide | formula
            $table->boolean('is_bidirectional')->default(true);
            $table->boolean('is_active')->default(true);

            // Optional: Product-specific conversion override
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('from_uom_id')->references('id')->on('unit_of_measures');
            $table->foreign('to_uom_id')->references('id')->on('unit_of_measures');
            $table->index(['from_uom_id', 'to_uom_id']);
        });

        // ── Product UOM Settings ────────────────────────────────────────────
        // Per-product/variant multi-UOM configuration.
        // Each product can have different UOMs for buying, selling, inventory.
        Schema::create('product_uom_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable(); // null = applies to all variants

            // Primary UOMs per product
            $table->unsignedBigInteger('base_uom_id');        // Internal reference UOM
            $table->unsignedBigInteger('inventory_uom_id');   // UOM for stock tracking
            $table->unsignedBigInteger('purchase_uom_id');    // UOM for purchase orders
            $table->unsignedBigInteger('sales_uom_id');       // UOM for sales orders
            $table->unsignedBigInteger('production_uom_id')->nullable(); // Manufacturing
            $table->unsignedBigInteger('shipping_uom_id')->nullable();   // For logistics

            // Conversion quantities (ratio: 1 purchase_uom = X base_uom)
            $table->decimal('purchase_to_base_ratio', 24, 10)->default(1.0000000000);
            $table->decimal('sales_to_base_ratio', 24, 10)->default(1.0000000000);
            $table->decimal('inventory_to_base_ratio', 24, 10)->default(1.0000000000);
            $table->decimal('production_to_base_ratio', 24, 10)->default(1.0000000000);

            // Packaging UOMs (optional layered packaging)
            $table->json('packaging_uoms')->nullable();
            /*
              [
                {"uom_id": 10, "label": "Inner Pack", "qty_per": 6},
                {"uom_id": 11, "label": "Outer Box",  "qty_per": 24},
                {"uom_id": 12, "label": "Pallet",     "qty_per": 144}
              ]
            */

            $table->boolean('allow_fractional_purchase')->default(true);
            $table->boolean('allow_fractional_sales')->default(true);
            $table->string('rounding_mode', 20)->default('round');
            // round | floor | ceil | none
            $table->decimal('rounding_precision', 10, 6)->default(0.01);

            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('base_uom_id')->references('id')->on('unit_of_measures');
            $table->foreign('inventory_uom_id')->references('id')->on('unit_of_measures');
            $table->foreign('purchase_uom_id')->references('id')->on('unit_of_measures');
            $table->foreign('sales_uom_id')->references('id')->on('unit_of_measures');
            $table->unique(['product_id', 'variant_id']);
        });

        // ── Pricing per UOM ─────────────────────────────────────────────────
        Schema::create('product_uom_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('price_list_id')->nullable(); // Pricelist-specific
            $table->decimal('price', 19, 6);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_uom_prices');
        Schema::dropIfExists('product_uom_settings');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('uom_categories');
    }
};
