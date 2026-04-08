<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warehouse Module — Multi-tenant, multi-org, multi-branch warehouse management.
 * Hierarchical location model: Warehouse → Zone → Aisle → Rack → Shelf → Bin
 * Supports all industry types: 3PL, retail, pharma, cold chain, hazmat, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Location Types (configurable hierarchy levels) ──────────────────
        Schema::create('warehouse_location_types', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('code', 50);
            $table->string('name', 100);  // Zone, Aisle, Rack, Shelf, Bin, Floor, Cell
            $table->string('icon', 50)->nullable();
            $table->boolean('is_storable')->default(true);   // Can goods be stored here?
            $table->boolean('is_virtual')->default(false);   // Virtual (transit, vendor, scrap)
            $table->integer('hierarchy_level')->default(1);  // 1=top, 6=bottom (bin)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        // ── Warehouses ──────────────────────────────────────────────────────
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('warehouse_type', 30)->default('standard');
            // standard | cold_chain | hazmat | bonded | consignment | 3pl | virtual | transit | dropship

            // Location
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Contact
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 150)->nullable();

            // Operations
            $table->unsignedBigInteger('warehouse_manager_id')->nullable(); // User
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('operating_days')->nullable(); // [1,2,3,4,5] = Mon-Fri

            // Virtual locations auto-created per warehouse
            $table->unsignedBigInteger('input_location_id')->nullable();    // Receiving staging
            $table->unsignedBigInteger('output_location_id')->nullable();   // Shipping staging
            $table->unsignedBigInteger('stock_location_id')->nullable();    // Main stock
            $table->unsignedBigInteger('quality_location_id')->nullable();  // QC hold
            $table->unsignedBigInteger('scrap_location_id')->nullable();    // Scrapped goods
            $table->unsignedBigInteger('return_location_id')->nullable();   // Customer returns

            // Capacity
            $table->decimal('total_area_sqm', 12, 2)->nullable();
            $table->decimal('total_volume_cbm', 12, 2)->nullable();
            $table->decimal('max_weight_kg', 12, 2)->nullable();
            $table->integer('total_locations_count')->default(0);

            // Settings flags
            $table->boolean('multi_step_receiving')->default(false);  // PO→Receipt→Put-away
            $table->boolean('multi_step_shipping')->default(false);   // Order→Pick→Pack→Ship
            $table->boolean('use_putaway_rules')->default(false);
            $table->boolean('use_replenishment_rules')->default(false);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('is_transit')->default(false);
            $table->boolean('is_virtual')->default(false);

            // Accounting
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->unsignedBigInteger('valuation_account_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();  // Extensible configuration
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });

        // ── Warehouse Locations (hierarchical) ──────────────────────────────
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('parent_id')->nullable();         // Self-referential
            $table->unsignedBigInteger('location_type_id')->nullable();  // Zone/Rack/Bin etc.

            $table->string('code', 100);          // e.g. A-01-02-03 (Aisle-Rack-Shelf-Bin)
            $table->string('name', 150);
            $table->string('barcode', 100)->nullable();  // Scannable location barcode
            $table->string('full_path', 500)->nullable(); // Computed: WH/Zone/Aisle/Rack/Shelf/Bin

            // Nested set for efficient tree queries
            $table->unsignedInteger('lft')->nullable()->index();
            $table->unsignedInteger('rgt')->nullable()->index();
            $table->unsignedInteger('depth')->default(0);

            // Location usage type
            $table->string('usage_type', 30)->default('storage');
            // storage | input | output | quality | scrap | transit | virtual | packing | production | rental

            // Physical properties
            $table->decimal('max_weight_kg', 12, 2)->nullable();
            $table->decimal('max_volume_cbm', 12, 4)->nullable();
            $table->decimal('max_units', 12, 2)->nullable();
            $table->decimal('length_cm', 10, 2)->nullable();
            $table->decimal('width_cm', 10, 2)->nullable();
            $table->decimal('height_cm', 10, 2)->nullable();
            $table->integer('pick_sequence')->nullable();  // Optimized pick path order

            // Environmental
            $table->string('temperature_zone', 30)->nullable();
            // ambient | refrigerated | frozen | controlled | hazmat
            $table->decimal('min_temp_celsius', 8, 2)->nullable();
            $table->decimal('max_temp_celsius', 8, 2)->nullable();
            $table->string('humidity_range', 30)->nullable();

            // Restrictions
            $table->boolean('is_bulk_storage')->default(false);    // FIFO bulk lanes
            $table->boolean('is_dedicated')->default(false);       // One SKU only
            $table->boolean('is_mixed_sku')->default(true);        // Multiple SKUs allowed
            $table->boolean('is_mixed_lot')->default(true);        // Multiple lots allowed
            $table->boolean('is_putaway_allowed')->default(true);
            $table->boolean('is_picking_allowed')->default(true);
            $table->boolean('is_cycle_count_location')->default(true);
            $table->boolean('is_quarantine')->default(false);
            $table->boolean('is_active')->default(true);

            // Product type restrictions
            $table->json('allowed_product_types')->nullable(); // Restrict to certain product types
            $table->json('allowed_categories')->nullable();    // Restrict to categories
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->unique(['warehouse_id', 'code']);
            $table->index('barcode');
            $table->index(['warehouse_id', 'usage_type']);
        });

        // ── Putaway Rules ──────────────────────────────────────────────────
        Schema::create('putaway_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name', 150);
            $table->string('strategy', 30)->default('fixed');
            // fixed | nearest_available | product_category | product_type | storage_zone | supplier | fifo_location

            // Matching conditions (all nullable = wildcard)
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('temperature_requirement', 30)->nullable();
            $table->boolean('is_hazmat')->nullable();

            // Target
            $table->unsignedBigInteger('destination_location_id')->nullable();
            $table->unsignedBigInteger('destination_zone_id')->nullable();
            $table->integer('priority')->default(10);  // Lower = checked first
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });

        // ── Replenishment Rules ─────────────────────────────────────────────
        Schema::create('replenishment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id');           // Pick face / forward location
            $table->unsignedBigInteger('source_location_id');    // Reserve / bulk location
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('uom_id');

            $table->string('trigger_type', 30)->default('min_max');
            // min_max | fixed_qty | demand_based | scheduled
            $table->decimal('min_qty', 19, 6);       // Trigger replenishment below this
            $table->decimal('max_qty', 19, 6);       // Replenish up to this
            $table->decimal('replenish_qty', 19, 6)->nullable(); // Fixed qty override
            $table->string('replenish_route', 30)->default('internal_transfer');
            // internal_transfer | purchase | manufacture

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('warehouse_locations');
            $table->foreign('source_location_id')->references('id')->on('warehouse_locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_rules');
        Schema::dropIfExists('putaway_rules');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('warehouse_location_types');
    }
};
