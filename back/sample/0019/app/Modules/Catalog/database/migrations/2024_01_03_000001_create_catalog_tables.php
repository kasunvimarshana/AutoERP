<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         |--------------------------------------------------------------------
         | Product Types
         | product, service, digital, combo, variable, rental, subscription
         |--------------------------------------------------------------------
         */
        Schema::create('product_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('has_variants')->default(false);
            $table->boolean('has_serial_tracking')->default(false);
            $table->boolean('has_batch_tracking')->default(false);
            $table->boolean('has_expiry_tracking')->default(false);
            $table->boolean('is_storable')->default(true)
                  ->comment('Can be physically stored in warehouse');
            $table->boolean('is_consumable')->default(false);
            $table->boolean('is_system')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        /*
         |--------------------------------------------------------------------
         | Product Categories (hierarchical, nested)
         |--------------------------------------------------------------------
         */
        Schema::create('product_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('lft')->nullable()->comment('Nested set left');
            $table->integer('rgt')->nullable()->comment('Nested set right');
            $table->integer('depth')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['lft', 'rgt']);
        });

        /*
         |--------------------------------------------------------------------
         | Attribute Sets (templates for product attributes)
         |--------------------------------------------------------------------
         */
        Schema::create('attribute_sets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });

        /*
         |--------------------------------------------------------------------
         | Attributes (Color, Size, Weight, etc.)
         |--------------------------------------------------------------------
         */
        Schema::create('attributes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->enum('input_type', ['text','number','select','multiselect','boolean','date','color','file'])
                  ->default('text');
            $table->boolean('is_variant_attribute')->default(false)
                  ->comment('Used to generate product variants');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('attribute_set_attributes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('attribute_set_id')->constrained('attribute_sets')->cascadeOnDelete();
            $table->foreignUlid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_set_id', 'attribute_id']);
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->string('color_code', 10)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['attribute_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Products (Master table - all product types)
         |--------------------------------------------------------------------
         */
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('product_type_id')->constrained('product_types')->restrictOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignUlid('attribute_set_id')->nullable()->constrained('attribute_sets')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable()->comment('Master SKU');
            $table->string('internal_reference')->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('status')->default('active')
                  ->comment('active,inactive,draft,archived,discontinued');
            $table->boolean('is_variable')->default(false)
                  ->comment('Has variants / options');
            $table->boolean('is_combo')->default(false)
                  ->comment('Bundle / kit product');
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_lot')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->boolean('has_expiry_date')->default(false);
            $table->integer('shelf_life_days')->nullable();
            $table->integer('best_before_days')->nullable()
                  ->comment('Alert days before expiry');
            $table->boolean('is_lot_controlled')->default(false);
            $table->decimal('weight', 12, 4)->nullable();
            $table->string('weight_uom_code', 20)->nullable();
            $table->decimal('length', 12, 4)->nullable();
            $table->decimal('width', 12, 4)->nullable();
            $table->decimal('height', 12, 4)->nullable();
            $table->string('dimension_uom_code', 20)->nullable();
            $table->decimal('volume', 12, 4)->nullable();
            $table->string('volume_uom_code', 20)->nullable();
            $table->decimal('standard_cost', 18, 4)->nullable();
            $table->decimal('list_price', 18, 4)->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->integer('min_stock_qty')->default(0);
            $table->integer('max_stock_qty')->nullable();
            $table->integer('reorder_point')->nullable();
            $table->integer('reorder_qty')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_rentable')->default(false);
            $table->boolean('is_manufactured')->default(false);
            $table->string('origin_country', 3)->nullable();
            $table->string('hs_code')->nullable()->comment('Harmonized System code for customs');
            // GS1 fields
            $table->string('gtin', 20)->nullable()->comment('GS1 Global Trade Item Number (GTIN-8/12/13/14)');
            $table->string('gs1_company_prefix')->nullable();
            $table->json('tags')->nullable();
            $table->json('meta')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'product_type_id']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['gtin']);
        });

        /*
         |--------------------------------------------------------------------
         | Product Barcodes (multiple per product, GS1-compatible)
         |--------------------------------------------------------------------
         */
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->ulid('product_variant_id')->nullable()
                  ->comment('NULL = applies to all variants');
            $table->string('barcode');
            $table->enum('barcode_type', ['EAN13','EAN8','UPC-A','UPC-E','QR','CODE128','CODE39','GS1-128','GTIN-14','SSCC','ITF-14','DataMatrix','PDF417','other'])
                  ->default('EAN13');
            $table->foreignUlid('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()
                  ->comment('UOM associated with this barcode (GS1 AI-3/4 support)');
            $table->decimal('quantity', 18, 4)->default(1)
                  ->comment('Quantity per scan (for bulk barcodes)');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['barcode', 'barcode_type']);
            $table->index(['product_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Product Variants
         |--------------------------------------------------------------------
         */
        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku');
            $table->string('internal_reference')->nullable();
            $table->string('gtin', 20)->nullable();
            $table->decimal('standard_cost', 18, 4)->nullable();
            $table->decimal('list_price', 18, 4)->nullable();
            $table->decimal('weight', 12, 4)->nullable();
            $table->decimal('length', 12, 4)->nullable();
            $table->decimal('width', 12, 4)->nullable();
            $table->decimal('height', 12, 4)->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignUlid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->foreignUlid('attribute_option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
            $table->text('value')->nullable()->comment('For free-text attributes');
            $table->timestamps();

            $table->unique(['product_variant_id', 'attribute_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Product Attribute Values (for non-variant attributes)
         |--------------------------------------------------------------------
         */
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->foreignUlid('attribute_option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'attribute_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Combo / Bundle Products (Bill of Materials)
         |--------------------------------------------------------------------
         */
        Schema::create('combo_product_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('combo_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('component_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->boolean('is_optional')->default(false);
            $table->decimal('price_override', 18, 4)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['combo_product_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Digital Product Files
         |--------------------------------------------------------------------
         */
        Schema::create('digital_product_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->bigInteger('file_size')->nullable()->comment('Bytes');
            $table->string('version')->nullable();
            $table->integer('download_limit')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['product_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Product Images
         |--------------------------------------------------------------------
         */
        Schema::create('product_images', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Supplier-Product Catalog (product pricing per supplier)
         |--------------------------------------------------------------------
         */
        Schema::create('supplier_product_catalog', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->ulid('supplier_id')->comment('FK set in Procurement module');
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignUlid('purchase_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->string('supplier_product_code')->nullable();
            $table->string('supplier_product_name')->nullable();
            $table->decimal('unit_price', 18, 4)->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->decimal('min_order_qty', 18, 4)->default(1);
            $table->decimal('multiple_order_qty', 18, 4)->default(1);
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'supplier_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_product_catalog');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('digital_product_files');
        Schema::dropIfExists('combo_product_items');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attribute_set_attributes');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_sets');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_types');
    }
};
