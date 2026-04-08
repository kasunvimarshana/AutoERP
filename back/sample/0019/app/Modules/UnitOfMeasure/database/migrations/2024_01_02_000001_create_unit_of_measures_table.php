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
         | UOM Categories
         | e.g. Weight, Volume, Length, Count, Area, Time, Digital, Custom
         |--------------------------------------------------------------------
         */
        Schema::create('uom_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)
                  ->comment('System-defined categories cannot be deleted');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /*
         |--------------------------------------------------------------------
         | Units of Measure
         |--------------------------------------------------------------------
         */
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('uom_category_id')->constrained('uom_categories')->restrictOnDelete();
            $table->foreignUlid('base_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete()
                  ->comment('NULL means this IS the base UOM for the category');
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('code', 50)->unique();
            $table->decimal('factor', 24, 10)->default(1.0)
                  ->comment('Conversion factor relative to base UOM');
            $table->decimal('rounding_precision', 10, 6)->default(0.01);
            $table->enum('rounding_method', ['up', 'down', 'half_up', 'half_down', 'none'])->default('half_up');
            $table->boolean('is_base')->default(false)
                  ->comment('Is this the base UOM for its category?');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            // GS1 / UNECE compatibility
            $table->string('gs1_code', 20)->nullable()
                  ->comment('GS1 AI-3 or UNECE unit code e.g. KGM, MTR, EA, CS');
            $table->string('unece_code', 10)->nullable()
                  ->comment('UN/ECE Rec.20 unit code');
            $table->string('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'uom_category_id']);
            $table->index(['is_base', 'uom_category_id']);
        });

        /*
         |--------------------------------------------------------------------
         | UOM Conversions (flexible overrides per tenant)
         |--------------------------------------------------------------------
         */
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('from_uom_id')->constrained('unit_of_measures')->cascadeOnDelete();
            $table->foreignUlid('to_uom_id')->constrained('unit_of_measures')->cascadeOnDelete();
            $table->decimal('factor', 24, 10)->comment('from * factor = to');
            $table->decimal('offset', 24, 10)->default(0)
                  ->comment('For non-proportional conversions (e.g., temperature)');
            $table->boolean('is_bidirectional')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'from_uom_id', 'to_uom_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Product UOM Configuration
         | Allows each product to define multiple UOM roles
         |--------------------------------------------------------------------
         */
        Schema::create('product_uom_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->ulid('product_id')->comment('FK set after catalog module migration');
            $table->foreignUlid('base_uom_id')->constrained('unit_of_measures')->restrictOnDelete()
                  ->comment('Inventory base UOM - all stock stored in this UOM');
            $table->foreignUlid('purchase_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()
                  ->comment('UOM used on purchase orders');
            $table->foreignUlid('sales_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()
                  ->comment('UOM used on sales orders');
            $table->foreignUlid('inventory_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()
                  ->comment('UOM for stock counting if different from base');
            $table->foreignUlid('production_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()
                  ->comment('UOM for manufacturing/production');
            $table->foreignUlid('shipping_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()
                  ->comment('UOM for shipping/logistics');
            $table->decimal('purchase_conversion_factor', 24, 10)->default(1.0)
                  ->comment('1 purchase UOM = X base UOM');
            $table->decimal('sales_conversion_factor', 24, 10)->default(1.0)
                  ->comment('1 sales UOM = X base UOM');
            $table->decimal('inventory_conversion_factor', 24, 10)->default(1.0);
            $table->decimal('production_conversion_factor', 24, 10)->default(1.0);
            $table->decimal('shipping_conversion_factor', 24, 10)->default(1.0);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id']);
            $table->index(['product_id']);
        });

        /*
         |--------------------------------------------------------------------
         | Product Variant UOM Config overrides
         |--------------------------------------------------------------------
         */
        Schema::create('product_variant_uom_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->ulid('product_variant_id');
            $table->foreignUlid('base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignUlid('purchase_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignUlid('sales_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('purchase_conversion_factor', 24, 10)->default(1.0);
            $table->decimal('sales_conversion_factor', 24, 10)->default(1.0);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_uom_configs');
        Schema::dropIfExists('product_uom_configs');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('uom_categories');
    }
};
