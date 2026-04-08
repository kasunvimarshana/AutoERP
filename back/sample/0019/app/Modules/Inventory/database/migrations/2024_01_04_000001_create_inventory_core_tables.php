<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory Module — Core inventory tracking tables.
 * Supports: FIFO, LIFO, AVCO, Standard Cost, Specific ID valuation.
 * Tracks: batches, lots, serial numbers, expiry dates.
 * Stock by: product × variant × warehouse × location × lot/batch × serial.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Inventory Settings (per warehouse/org) ──────────────────────────
        Schema::create('inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable(); // null = org-wide default

            // Inventory Valuation Method (user-configurable per warehouse/product)
            $table->string('default_costing_method', 30)->default('avco');
            // fifo | lifo | avco | standard | specific_identification | fefo

            // Inventory Management
            $table->string('inventory_management_method', 30)->default('perpetual');
            // perpetual | periodic

            // Stock Rotation Strategy (picking order)
            $table->string('stock_rotation_strategy', 30)->default('fifo');
            // fifo | lifo | fefo | lefo | fmfo | sled | manual | fefo_fifo
            // FEFO=First Expiry First Out, LEFO=Last Expiry First Out
            // FMFO=First Manufactured First Out, SLED=Shortest Life Expiry Date

            // Allocation Algorithm (for order fulfillment)
            $table->string('allocation_algorithm', 30)->default('standard');
            // standard | priority | fair_share | manual | wave | zone | cluster

            // Negative stock
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('warn_on_negative_stock')->default(true);

            // Tracking defaults
            $table->boolean('track_serial_numbers')->default(false);
            $table->boolean('track_batches')->default(false);
            $table->boolean('track_lots')->default(false);
            $table->boolean('track_expiry_date')->default(false);
            $table->boolean('track_manufacture_date')->default(false);
            $table->boolean('track_best_before_date')->default(false);
            $table->boolean('track_cost_per_unit')->default(true);
            $table->boolean('auto_lot_on_receipt')->default(true);
            $table->string('lot_number_format', 100)->nullable(); // e.g. LOT-{YYYY}-{SEQ:6}
            $table->string('serial_number_format', 100)->nullable();

            // Cycle counting
            $table->string('cycle_count_method', 30)->default('abc');
            // abc | periodic | continuous | location_based | random | zero_balance

            // Expiry management
            $table->integer('default_expiry_alert_days')->default(30);
            $table->boolean('auto_quarantine_expired')->default(false);
            $table->boolean('block_expired_stock_movement')->default(true);
            $table->boolean('allow_movement_near_expiry')->default(true);
            $table->integer('near_expiry_threshold_days')->default(30);

            // Rounding
            $table->decimal('quantity_rounding_precision', 8, 4)->default(0.0100);

            // Multi-step operations
            $table->boolean('require_quality_check_on_receipt')->default(false);
            $table->boolean('require_quality_check_on_return')->default(false);

            $table->json('custom_settings')->nullable();  // Extensible per tenant

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_id', 'warehouse_id']);
        });

        // ── Tracking Lots (unified batch + lot tracking) ─────────────────────
        // A "lot" represents a group of units received/produced at the same time.
        // Can map to: Batch Number, Lot Number, Run Number, Dye Lot, etc.
        Schema::create('tracking_lots', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();

            $table->string('lot_number', 150);        // User-assigned or auto-generated
            $table->string('lot_type', 30)->default('batch');
            // batch | lot | run | dye_lot | production_lot | supplier_batch

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();

            // Supplier / Origin
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('supplier_lot_number', 150)->nullable(); // Supplier's own batch ref
            $table->string('country_of_origin', 10)->nullable();
            $table->string('manufacturer_name', 150)->nullable();
            $table->string('manufacturer_lot_number', 150)->nullable();

            // Dates
            $table->date('manufacture_date')->nullable();
            $table->date('receipt_date')->nullable();
            $table->date('expiry_date')->nullable();          // Used for FEFO
            $table->date('best_before_date')->nullable();     // Consume by date
            $table->date('retest_date')->nullable();          // Pharma retesting date
            $table->date('quarantine_release_date')->nullable();

            // Quantities
            $table->decimal('initial_qty', 19, 6)->default(0);
            $table->decimal('current_qty', 19, 6)->default(0);   // Computed/cached
            $table->unsignedBigInteger('uom_id')->nullable();

            // Quality / Status
            $table->string('status', 30)->default('available');
            // available | quarantine | released | rejected | recalled | consumed | expired | scrapped
            $table->boolean('is_quarantined')->default(false);
            $table->string('quarantine_reason', 255)->nullable();
            $table->boolean('is_recalled')->default(false);
            $table->string('recall_reference', 100)->nullable();
            $table->text('quality_notes')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('released_at')->nullable();

            // Cost
            $table->decimal('unit_cost', 19, 6)->nullable();   // Cost at time of receipt
            $table->unsignedBigInteger('currency_id')->nullable();

            // GS1 / Regulatory
            $table->string('gs1_batch_lot', 100)->nullable();   // GS1 AI(10)
            $table->boolean('gs1_enabled')->default(false);
            $table->string('regulatory_approval_number', 100)->nullable(); // Pharma/Health
            $table->string('certificate_of_analysis', 255)->nullable();    // CoA file path

            // Traceability
            $table->unsignedBigInteger('receipt_id')->nullable();          // Source receipt
            $table->unsignedBigInteger('purchase_order_id')->nullable();   // Source PO
            $table->unsignedBigInteger('production_order_id')->nullable(); // If manufactured
            $table->string('external_reference', 150)->nullable();

            $table->json('custom_attributes')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index(['tenant_id', 'lot_number']);
            $table->index(['product_id', 'expiry_date']);
            $table->index(['tenant_id', 'status']);
        });

        // ── Serial Numbers ──────────────────────────────────────────────────
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();    // Optional lot association

            $table->string('serial_number', 150);
            $table->string('internal_reference', 150)->nullable(); // Internal tracking ref

            // Status
            $table->string('status', 30)->default('in_stock');
            // in_stock | sold | rented | returned | scrapped | lost | in_transit | quarantine | recalled

            // Current location
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();

            // Ownership tracking (for sold/rented serials)
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('sale_date')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->date('rental_start_date')->nullable();
            $table->date('rental_end_date')->nullable();

            // Dates
            $table->date('manufacture_date')->nullable();
            $table->date('receipt_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Cost
            $table->decimal('unit_cost', 19, 6)->nullable();

            // GS1
            $table->string('gs1_serial', 100)->nullable();    // GS1 AI(21)
            $table->string('gtin', 50)->nullable();
            $table->boolean('gs1_enabled')->default(false);

            // Traceability
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('supplier_serial_number', 150)->nullable();

            $table->json('custom_attributes')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('tracking_lots')->nullOnDelete();
            $table->index(['tenant_id', 'serial_number']);
            $table->index(['product_id', 'status']);
            $table->unique(['tenant_id', 'product_id', 'serial_number']);
        });

        // ── Stock Levels (current on-hand inventory) ────────────────────────
        // Aggregated and real-time stock per: product×variant×warehouse×location×lot
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();

            // Dimensional keys (what exactly we're tracking)
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();        // Batch/lot tracking
            $table->unsignedBigInteger('uom_id');                    // Inventory UOM

            // Quantity tracking
            $table->decimal('qty_on_hand', 19, 6)->default(0);       // Physical stock
            $table->decimal('qty_reserved', 19, 6)->default(0);      // Reserved for orders
            $table->decimal('qty_available', 19, 6)->default(0);     // on_hand - reserved
            $table->decimal('qty_in_transit', 19, 6)->default(0);    // Moving between locations
            $table->decimal('qty_incoming', 19, 6)->default(0);      // Ordered, not received
            $table->decimal('qty_outgoing', 19, 6)->default(0);      // Sold, not delivered
            $table->decimal('qty_quarantine', 19, 6)->default(0);    // On hold / QC
            $table->decimal('qty_virtual', 19, 6)->default(0);       // Projected future qty
            $table->decimal('qty_scrapped', 19, 6)->default(0);      // Cumulative scrapped

            // Valuation
            $table->decimal('unit_cost', 19, 6)->default(0);
            $table->decimal('total_value', 19, 6)->default(0);       // qty × unit_cost
            $table->unsignedBigInteger('currency_id')->nullable();

            // Timestamps for FEFO / FIFO ordering
            $table->timestamp('first_in_date')->nullable();   // Earliest receipt date
            $table->timestamp('last_move_date')->nullable();  // Last movement date

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('tracking_lots')->nullOnDelete();

            // Unique combination — one row per dimensional intersection
            $table->unique(
                ['tenant_id', 'product_id', 'variant_id', 'warehouse_id', 'location_id', 'lot_id'],
                'stock_levels_unique'
            );
            $table->index(['warehouse_id', 'product_id']);
            $table->index(['tenant_id', 'product_id', 'qty_available']);
        });

        // ── Inventory Valuation Layers ──────────────────────────────────────
        // Core of FIFO/LIFO/AVCO cost accounting.
        // Each receipt creates a layer; issues consume layers in order.
        Schema::create('inventory_valuation_layers', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('lot_id')->nullable();

            $table->string('costing_method', 30);
            // fifo | lifo | avco | standard | specific_identification

            $table->string('layer_type', 30)->default('receipt');
            // receipt | return | adjustment | opening | revaluation

            // Reference to source transaction
            $table->string('reference_type', 100)->nullable();    // Polymorphic
            $table->unsignedBigInteger('reference_id')->nullable();

            // Quantities and cost
            $table->decimal('initial_qty', 19, 6);            // Qty when layer created
            $table->decimal('remaining_qty', 19, 6);          // Qty not yet consumed
            $table->decimal('unit_cost', 19, 6);              // Cost per unit at creation
            $table->decimal('total_cost', 19, 6);             // initial_qty × unit_cost
            $table->decimal('remaining_value', 19, 6);        // remaining_qty × unit_cost
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();

            // FIFO/LIFO ordering
            $table->timestamp('layer_date');                   // Date of this cost layer
            $table->integer('sequence')->default(0);           // Tie-break for same date

            $table->boolean('is_fully_consumed')->default(false);
            $table->timestamp('fully_consumed_at')->nullable();

            // Cost adjustments (e.g. landed costs applied later)
            $table->decimal('adjustment_cost', 19, 6)->default(0);
            $table->text('adjustment_notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->index(['product_id', 'warehouse_id', 'costing_method', 'is_fully_consumed']);
            $table->index(['tenant_id', 'layer_date']);
        });

        // ── Standard Costs ─────────────────────────────────────────────────
        Schema::create('standard_costs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable(); // null = all warehouses
            $table->decimal('standard_cost', 19, 6);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_costs');
        Schema::dropIfExists('inventory_valuation_layers');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('tracking_lots');
        Schema::dropIfExists('inventory_settings');
    }
};
