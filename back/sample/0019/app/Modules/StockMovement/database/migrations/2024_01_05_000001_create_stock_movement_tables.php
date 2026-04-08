<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * StockMovement Module — All physical goods movements.
 * Covers: receipts, deliveries, internal transfers, adjustments,
 * scrap, inter-company, production input/output, kit assembly/disassembly.
 * All movements are double-entry (source → destination location).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Operation Types (configurable workflow types) ────────────────────
        Schema::create('stock_operation_types', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable(); // null = global

            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('category', 30)->default('internal');
            // incoming | outgoing | internal | manufacturing | returns | scrap

            // Default locations
            $table->unsignedBigInteger('default_source_location_id')->nullable();
            $table->unsignedBigInteger('default_destination_location_id')->nullable();

            // Sequence / numbering
            $table->string('sequence_prefix', 20)->nullable();  // e.g. REC, DEL, INT, ADJ
            $table->string('sequence_format', 100)->nullable(); // e.g. {PREFIX}/{YYYY}/{SEQ:5}
            $table->integer('next_sequence')->default(1);

            // Multi-step workflows
            $table->boolean('use_create_lots')->default(false);
            $table->boolean('use_existing_lots')->default(false);
            $table->boolean('use_serial_numbers')->default(false);
            $table->boolean('require_source_location')->default(true);
            $table->boolean('require_destination_location')->default(true);
            $table->boolean('create_backorder')->default(true);  // If partial, create backorder
            $table->boolean('auto_validate')->default(false);    // Skip manual validation
            $table->boolean('show_detailed_operations')->default(true);

            // Approval
            $table->boolean('require_approval')->default(false);
            $table->string('approval_policy', 30)->nullable(); // any_approver | all_approvers
            $table->json('approver_user_ids')->nullable();
            $table->decimal('approval_amount_threshold', 19, 6)->nullable();

            // Accounting
            $table->boolean('generate_journal_entry')->default(false);
            $table->unsignedBigInteger('journal_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });

        // ── Stock Movements (Header) ─────────────────────────────────────────
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->string('reference_number', 100);       // Auto-generated reference
            $table->string('external_reference', 150)->nullable(); // External PO#, SO#, etc.

            $table->unsignedBigInteger('operation_type_id');

            // Locations (null allowed for virtual/adjustment)
            $table->unsignedBigInteger('source_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();

            // Status
            $table->string('status', 30)->default('draft');
            // draft | confirmed | ready | in_progress | done | cancelled | waiting

            // Scheduling
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('effective_date')->nullable();  // When stock actually moved
            $table->timestamp('completed_at')->nullable();

            // Responsible parties
            $table->unsignedBigInteger('responsible_user_id')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Source document (polymorphic link to PO, SO, return, etc.)
            $table->string('source_document_type', 100)->nullable();
            $table->unsignedBigInteger('source_document_id')->nullable();
            $table->string('source_document_ref', 150)->nullable();

            // Backorder tracking
            $table->unsignedBigInteger('backorder_of_id')->nullable(); // Parent movement
            $table->unsignedBigInteger('origin_id')->nullable();       // Original movement chain

            // Carrier / Shipping
            $table->string('carrier_name', 100)->nullable();
            $table->string('tracking_number', 150)->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->decimal('shipping_cost', 19, 6)->nullable();

            // Quality check
            $table->boolean('requires_quality_check')->default(false);
            $table->string('quality_check_status', 30)->nullable();
            // pending | in_progress | passed | failed

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('operation_type_id')->references('id')->on('stock_operation_types');
            $table->foreign('source_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->foreign('destination_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->foreign('backorder_of_id')->references('id')->on('stock_movements')->nullOnDelete();

            $table->index(['tenant_id', 'reference_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['source_document_type', 'source_document_id']);
            $table->index('effective_date');
        });

        // ── Stock Movement Lines ─────────────────────────────────────────────
        Schema::create('stock_movement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movement_id');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_description', 500)->nullable(); // Snapshot at time of move

            // Location overrides (can differ from header)
            $table->unsignedBigInteger('source_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();

            // Lot / serial tracking
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->string('lot_number_input', 150)->nullable(); // For creation of new lots
            $table->date('expiry_date_input')->nullable();       // For lot creation

            // Quantities
            $table->decimal('demand_qty', 19, 6)->default(0);   // Originally requested
            $table->decimal('done_qty', 19, 6)->default(0);     // Actually moved
            $table->unsignedBigInteger('uom_id');                // UOM for this movement
            $table->decimal('uom_factor', 19, 10)->default(1.0); // Conversion factor to base

            // Costing
            $table->decimal('unit_cost', 19, 6)->default(0);
            $table->decimal('total_cost', 19, 6)->default(0);   // done_qty × unit_cost
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('costing_method', 30)->nullable();   // Method used to cost this line
            $table->unsignedBigInteger('valuation_layer_id')->nullable(); // Layer consumed

            // Status
            $table->string('status', 30)->default('draft');
            // draft | confirmed | done | cancelled | backorder

            $table->boolean('is_backorder')->default(false);
            $table->decimal('backorder_qty', 19, 6)->default(0);  // Remaining if partial
            $table->unsignedBigInteger('backorder_line_id')->nullable();

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('movement_id')->references('id')->on('stock_movements')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('tracking_lots')->nullOnDelete();
            $table->foreign('source_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->foreign('destination_location_id')->references('id')->on('warehouse_locations')->nullOnDelete();
            $table->index('movement_id');
            $table->index(['product_id', 'lot_id']);
        });

        // ── Serial Number moves (1:N per movement line) ─────────────────────
        Schema::create('stock_movement_serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movement_line_id');
            $table->unsignedBigInteger('serial_number_id');
            $table->string('serial_number', 150);       // Snapshot
            $table->string('action', 30)->default('move'); // move | create | consume | return
            $table->timestamp('moved_at')->nullable();
            $table->timestamps();

            $table->foreign('movement_line_id')->references('id')->on('stock_movement_lines')->cascadeOnDelete();
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers');
        });

        // ── Stock Adjustments (inventory discrepancy entries) ────────────────
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');

            $table->string('reference_number', 100);
            $table->string('adjustment_type', 30)->default('quantity');
            // quantity | valuation | opening | reconciliation | write_off | cycle_count

            $table->string('reason', 30)->nullable();
            // damage | theft | counting_error | system_correction | expiry | natural_loss | other

            $table->string('status', 30)->default('draft');
            $table->timestamp('adjustment_date');
            $table->unsignedBigInteger('movement_id')->nullable(); // Linked movement
            $table->unsignedBigInteger('cycle_count_session_id')->nullable();

            $table->decimal('total_value_impact', 19, 6)->default(0);
            $table->boolean('approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->index(['tenant_id', 'reference_number']);
        });

        // ── Inventory Scrap ─────────────────────────────────────────────────
        Schema::create('inventory_scrap', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');

            $table->string('reference_number', 100);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('scrap_location_id')->nullable();

            $table->decimal('scrap_qty', 19, 6);
            $table->unsignedBigInteger('uom_id');
            $table->string('scrap_reason', 50)->nullable();
            // expired | damaged | quality_fail | obsolete | recalled | other

            $table->decimal('unit_cost', 19, 6)->nullable();
            $table->decimal('total_cost', 19, 6)->nullable();
            $table->unsignedBigInteger('movement_id')->nullable();

            $table->string('status', 30)->default('draft');
            $table->timestamp('scrap_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
        });

        // ── Inter-company / Inter-branch Transfers ──────────────────────────
        Schema::create('inter_company_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('reference_number', 100);

            $table->unsignedBigInteger('source_organization_id');
            $table->unsignedBigInteger('destination_organization_id');
            $table->unsignedBigInteger('source_warehouse_id');
            $table->unsignedBigInteger('destination_warehouse_id');

            // When crossing org boundaries, create mirror documents
            $table->unsignedBigInteger('outgoing_movement_id')->nullable(); // Source side
            $table->unsignedBigInteger('incoming_movement_id')->nullable(); // Dest side
            $table->unsignedBigInteger('intercompany_po_id')->nullable();   // Auto-PO at dest
            $table->unsignedBigInteger('intercompany_so_id')->nullable();   // Auto-SO at source

            $table->string('status', 30)->default('draft');
            $table->timestamp('transfer_date');
            $table->decimal('transfer_value', 19, 6)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('pricing_policy', 30)->default('cost');
            // cost | sales_price | transfer_price | manual

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inter_company_transfers');
        Schema::dropIfExists('inventory_scrap');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movement_serials');
        Schema::dropIfExists('stock_movement_lines');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_operation_types');
    }
};
