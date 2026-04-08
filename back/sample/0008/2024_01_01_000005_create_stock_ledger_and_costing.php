<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Stock On Hand (current position snapshot) ───────────────────────
        // This is the real-time inventory position table. Updated on every movement.
        Schema::create('stock_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();

            // ── Quantity Buckets ───────────────────────────────────────────
            $table->decimal('qty_on_hand', 20, 4)->default(0);
            $table->decimal('qty_available', 20, 4)->default(0);      // on_hand - reserved
            $table->decimal('qty_reserved', 20, 4)->default(0);       // soft/hard allocated
            $table->decimal('qty_on_order', 20, 4)->default(0);       // PO received not yet in stock
            $table->decimal('qty_in_transit', 20, 4)->default(0);     // transfer in progress
            $table->decimal('qty_quarantine', 20, 4)->default(0);
            $table->decimal('qty_damaged', 20, 4)->default(0);
            $table->decimal('qty_returned', 20, 4)->default(0);

            // ── Running Average Cost (for AVCO) ───────────────────────────
            $table->decimal('average_cost', 20, 4)->default(0);
            $table->decimal('total_cost_value', 20, 4)->default(0);  // qty_on_hand * avg_cost

            // ── Last Movement ──────────────────────────────────────────────
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamp('last_counted_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['product_id', 'product_variant_id', 'warehouse_id', 'storage_location_id', 'lot_id', 'batch_id'],
                'stock_position_unique'
            );
            $table->index(['organization_id', 'product_id']);
            $table->index(['warehouse_id', 'product_id']);
        });

        // ─── Stock Ledger (immutable double-entry journal) ───────────────────
        // Every inventory movement creates at least one ledger entry.
        // NEVER UPDATE OR DELETE — append-only.
        Schema::create('stock_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number')->index(); // unique journal ref e.g. JRN-2024-000001
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();

            // ── Movement Type ──────────────────────────────────────────────
            $table->string('movement_type');
            // purchase_receipt | purchase_return | sales_issue | sales_return
            // transfer_out | transfer_in | adjustment_positive | adjustment_negative
            // production_consume | production_produce | scrap | write_off
            // opening_balance | physical_count_adjustment | cycle_count_adjustment
            // assembly_disassemble | kit_build | kit_disassemble
            // consignment_in | consignment_out | damage | quarantine | quarantine_release
            // expired_removal | sample | donation | theft | other

            $table->string('direction'); // IN | OUT
            $table->decimal('quantity', 20, 4);                    // always positive
            $table->decimal('quantity_before', 20, 4)->default(0); // running balance before
            $table->decimal('quantity_after', 20, 4)->default(0);  // running balance after

            // ── Costing ────────────────────────────────────────────────────
            $table->string('valuation_method');     // snapped at time of entry
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->decimal('average_cost_before', 20, 4)->nullable(); // for AVCO calc
            $table->decimal('average_cost_after', 20, 4)->nullable();

            // ── Source Document ────────────────────────────────────────────
            $table->string('source_document_type')->nullable(); // purchase_order|sales_order|transfer|adjustment|etc
            $table->unsignedBigInteger('source_document_id')->nullable();
            $table->string('source_document_number')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();

            // ── Reason & Traceability ──────────────────────────────────────
            $table->string('reason_code')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('movement_date'); // actual date of movement (may differ from created_at)

            $table->timestamps(); // immutable — never updated after insert

            $table->index(['product_id', 'warehouse_id', 'movement_date']);
            $table->index(['source_document_type', 'source_document_id']);
            $table->index('movement_type');
            $table->index('movement_date');
        });

        // ─── Costing Layers (for FIFO / LIFO / Specific Identification) ──────
        // Each receipt creates a "layer". Issues consume from layers based on method.
        Schema::create('costing_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('valuation_method'); // FIFO|LIFO|FEFO|FMFO|specific_id
            $table->string('layer_reference');   // ties to ledger reference_number

            $table->decimal('initial_quantity', 20, 4);
            $table->decimal('remaining_quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            $table->decimal('total_cost', 20, 4);

            $table->date('manufacture_date')->nullable();  // for FEFO/FMFO sorting
            $table->date('expiry_date')->nullable();        // for FEFO sorting
            $table->timestamp('received_at');               // for FIFO/LIFO sorting

            $table->boolean('is_fully_consumed')->default(false);
            $table->timestamp('fully_consumed_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'is_fully_consumed']);
            $table->index(['product_id', 'received_at']); // FIFO
            $table->index(['product_id', 'expiry_date']);  // FEFO
        });

        // ─── Costing Layer Consumptions (trail of how layers were consumed) ───
        Schema::create('costing_layer_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_layer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_entry_id')->constrained('stock_ledger_entries')->cascadeOnDelete();
            $table->decimal('quantity_consumed', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            $table->decimal('total_cost', 20, 4);
            $table->timestamps();
        });

        // ─── Standard Cost Variances ─────────────────────────────────────────
        Schema::create('cost_variances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ledger_entry_id')->constrained('stock_ledger_entries')->cascadeOnDelete();
            $table->string('variance_type'); // purchase_price|usage|efficiency|overhead
            $table->decimal('standard_cost', 20, 4);
            $table->decimal('actual_cost', 20, 4);
            $table->decimal('variance_amount', 20, 4);
            $table->decimal('quantity', 20, 4);
            $table->string('period'); // YYYY-MM for period rollup
            $table->timestamps();

            $table->index(['product_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_variances');
        Schema::dropIfExists('costing_layer_consumptions');
        Schema::dropIfExists('costing_layers');
        Schema::dropIfExists('stock_ledger_entries');
        Schema::dropIfExists('stock_positions');
    }
};
