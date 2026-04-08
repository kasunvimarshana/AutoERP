<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product Variants, Barcodes, Supplier Info, Media, and Combo/Bundle items.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Product Variants ────────────────────────────────────────────────
        // For variable products (e.g. T-Shirt in Red/L, Red/XL, Blue/L…)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');       // Parent product
            $table->string('sku', 100)->nullable();
            $table->string('name', 255)->nullable();        // e.g. "Red / XL"
            $table->string('internal_reference', 100)->nullable();

            // Variant-specific overrides
            $table->decimal('standard_cost', 19, 6)->nullable();
            $table->decimal('sales_price', 19, 6)->nullable();
            $table->decimal('weight', 12, 4)->nullable();
            $table->decimal('volume', 12, 4)->nullable();
            $table->string('image_path')->nullable();

            // UOM overrides (nullable = inherit from parent)
            $table->unsignedBigInteger('base_uom_id')->nullable();
            $table->unsignedBigInteger('purchase_uom_id')->nullable();
            $table->unsignedBigInteger('sales_uom_id')->nullable();

            // Tracking overrides
            $table->boolean('track_serial_numbers')->nullable();
            $table->boolean('track_batches')->nullable();
            $table->boolean('track_expiry_date')->nullable();

            // Stock thresholds override
            $table->decimal('reorder_point', 19, 6)->nullable();
            $table->decimal('reorder_qty', 19, 6)->nullable();
            $table->decimal('safety_stock_qty', 19, 6)->nullable();

            $table->string('gtin', 50)->nullable();    // Variant-level GTIN
            $table->string('status', 30)->default('active');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['tenant_id', 'sku']);
            $table->index('product_id');
        });

        // ── Variant ↔ Attribute Value mapping ───────────────────────────────
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id')->nullable(); // For select types
            $table->string('custom_value', 255)->nullable();              // For text/number types
            $table->timestamps();

            $table->foreign('variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->foreign('attribute_id')->references('id')->on('product_attributes');
            $table->foreign('attribute_value_id')->references('id')->on('product_attribute_values')->nullOnDelete();
            $table->unique(['variant_id', 'attribute_id']);
        });

        // ── Product Custom Attribute Values (product-level, not variant) ─────
        Schema::create('product_attribute_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id')->nullable();
            $table->text('custom_value')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('attribute_id')->references('id')->on('product_attributes');
            $table->unique(['product_id', 'attribute_id']);
        });

        // ── Barcodes (GS1-compatible) ────────────────────────────────────────
        // One product/variant can have multiple barcodes (UPC-A, EAN-13, ITF-14, QR, etc.)
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();

            $table->string('barcode', 100);
            $table->string('barcode_type', 30)->default('ean13');
            // ean13|ean8|upc_a|upc_e|code128|code39|qr|gs1_128|itf14|sscc|gtin14|datamatrix

            // GS1 Application Identifiers (AI) for GS1-128 / DataMatrix
            $table->string('gs1_gtin', 20)->nullable();         // AI(01)
            $table->string('gs1_batch_lot', 30)->nullable();    // AI(10)
            $table->string('gs1_serial', 30)->nullable();       // AI(21)
            $table->date('gs1_expiry_date')->nullable();        // AI(17)
            $table->date('gs1_manufacture_date')->nullable();   // AI(11)
            $table->decimal('gs1_net_weight', 12, 4)->nullable(); // AI(310x)
            $table->string('gs1_sscc', 25)->nullable();         // AI(00) - Shipping Container
            $table->json('gs1_additional_ais')->nullable();     // Other GS1 AIs

            $table->boolean('is_primary')->default(false);      // Primary/default barcode
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index('barcode');
            $table->index(['tenant_id', 'barcode']);
        });

        // ── Supplier / Vendor Product Catalog Info ───────────────────────────
        Schema::create('product_supplier_info', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('supplier_id');      // FK to suppliers/partners

            $table->string('supplier_product_code', 100)->nullable();  // Supplier's SKU
            $table->string('supplier_product_name', 255)->nullable();
            $table->string('supplier_barcode', 100)->nullable();
            $table->string('supplier_uom', 50)->nullable();     // Supplier's UOM
            $table->decimal('supplier_uom_qty', 19, 6)->default(1); // Qty per supplier UOM

            $table->decimal('purchase_price', 19, 6)->nullable();
            $table->unsignedBigInteger('price_currency_id')->nullable();
            $table->date('price_valid_from')->nullable();
            $table->date('price_valid_to')->nullable();

            $table->decimal('min_order_qty', 19, 6)->nullable();      // MOQ
            $table->decimal('multiple_order_qty', 19, 6)->nullable(); // Order in multiples
            $table->integer('lead_time_days')->default(0);
            $table->decimal('discount_pct', 8, 4)->nullable();

            $table->boolean('is_primary_supplier')->default(false);
            $table->integer('priority')->default(10);  // Lower = higher priority
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index(['product_id', 'supplier_id']);
        });

        // ── Combo / Bundle / Kit Items ───────────────────────────────────────
        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('combo_product_id');  // Parent combo product
            $table->unsignedBigInteger('component_product_id'); // Component product
            $table->unsignedBigInteger('component_variant_id')->nullable();
            $table->decimal('quantity', 19, 6)->default(1);
            $table->unsignedBigInteger('uom_id')->nullable();

            // Pricing contribution
            $table->decimal('price_contribution', 19, 6)->nullable();
            $table->boolean('is_optional')->default(false);     // Optional components
            $table->boolean('is_variable_qty')->default(false); // Customer can change qty
            $table->decimal('min_qty', 19, 6)->nullable();
            $table->decimal('max_qty', 19, 6)->nullable();

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('combo_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('component_product_id')->references('id')->on('products');
            $table->foreign('component_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        // ── Product Media (images, videos, documents, certificates) ─────────
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('media_type', 30)->default('image'); // image|video|document|certificate
            $table->string('file_path', 500);
            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        // ── Product Tags ─────────────────────────────────────────────────────
        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('name', 100);
            $table->string('slug', 120)->nullable();
            $table->string('color', 10)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('product_tag_pivot', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['product_id', 'tag_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('product_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tag_pivot');
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_combo_items');
        Schema::dropIfExists('product_supplier_info');
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('product_attribute_data');
        Schema::dropIfExists('product_variant_attributes');
        Schema::dropIfExists('product_variants');
    }
};
