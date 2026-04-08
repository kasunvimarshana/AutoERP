<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Stock Positions (real-time snapshot) ──────────────────────────────
        Schema::create('stock_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('storage_location_id')->nullable()->index();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('uom_id')->nullable();

            $table->decimal('qty_on_hand', 20, 4)->default(0);
            $table->decimal('qty_available', 20, 4)->default(0);
            $table->decimal('qty_reserved', 20, 4)->default(0);
            $table->decimal('qty_on_order', 20, 4)->default(0);
            $table->decimal('qty_in_transit', 20, 4)->default(0);
            $table->decimal('qty_quarantine', 20, 4)->default(0);
            $table->decimal('qty_damaged', 20, 4)->default(0);
            $table->decimal('qty_returned', 20, 4)->default(0);

            $table->decimal('average_cost', 20, 6)->default(0);
            $table->decimal('total_cost_value', 20, 4)->default(0);

            $table->timestamp('last_movement_at')->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['product_id','product_variant_id','warehouse_id','storage_location_id','lot_id','batch_id'],
                'stock_position_unique'
            );
            $table->index(['tenant_id', 'product_id']);
            $table->index(['warehouse_id', 'product_id']);
        });

        // ── Stock Ledger (IMMUTABLE append-only double-entry journal) ─────────
        Schema::create('stock_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference_number')->unique();  // JRN-2025-000001
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();

            $table->string('movement_type');
            // purchase_receipt|purchase_return|sales_issue|sales_return
            // transfer_out|transfer_in|adjustment_positive|adjustment_negative
            // production_consume|production_produce|scrap|write_off
            // opening_balance|physical_count_adjustment|cycle_count_adjustment
            // assembly_build|assembly_disassemble|kit_build|kit_disassemble
            // consignment_in|consignment_out|damage|quarantine|quarantine_release
            // expired_removal|sample|donation|theft|rework|return_to_vendor|other

            $table->string('direction', 3);  // IN | OUT

            $table->decimal('quantity', 20, 4);
            $table->decimal('quantity_before', 20, 4)->default(0);
            $table->decimal('quantity_after', 20, 4)->default(0);

            $table->string('valuation_method', 30);
            $table->decimal('unit_cost', 20, 6)->default(0);
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->decimal('average_cost_before', 20, 6)->nullable();
            $table->decimal('average_cost_after', 20, 6)->nullable();

            // ── Source document ────────────────────────────────────────────
            $table->string('source_document_type')->nullable();
            $table->unsignedBigInteger('source_document_id')->nullable();
            $table->string('source_document_number')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();

            $table->string('reason_code')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('movement_date');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'movement_date']);
            $table->index(['source_document_type', 'source_document_id']);
            $table->index('movement_type');
            $table->index('movement_date');
        });

        // ── Costing Layers (FIFO/LIFO/FEFO/FMFO/specific_id) ─────────────────
        Schema::create('costing_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('valuation_method', 30);
            $table->string('layer_reference');
            $table->decimal('initial_quantity', 20, 4);
            $table->decimal('remaining_quantity', 20, 4);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 4);
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('received_at');
            $table->boolean('is_fully_consumed')->default(false);
            $table->timestamp('fully_consumed_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'is_fully_consumed']);
            $table->index(['product_id', 'received_at']);
            $table->index(['product_id', 'expiry_date']);
        });

        Schema::create('costing_layer_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('costing_layer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('ledger_entry_id')->index();
            $table->decimal('quantity_consumed', 20, 4);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 4);
            $table->timestamps();
        });

        // ── Standard Cost Variances ───────────────────────────────────────────
        Schema::create('cost_variances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('ledger_entry_id')->index();
            $table->string('variance_type');
            // purchase_price|usage|efficiency|overhead|exchange_rate
            $table->decimal('standard_cost', 20, 6);
            $table->decimal('actual_cost', 20, 6);
            $table->decimal('variance_amount', 20, 4);
            $table->decimal('quantity', 20, 4);
            $table->string('period');  // YYYY-MM
            $table->timestamps();

            $table->index(['product_id', 'period']);
        });

        // ── Document Sequences ────────────────────────────────────────────────
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('document_type');
            $table->string('prefix')->nullable();
            $table->integer('next_number')->default(1);
            $table->integer('padding')->default(6);
            $table->string('separator')->default('-');
            $table->boolean('include_year')->default(true);
            $table->boolean('include_month')->default(false);
            $table->boolean('reset_on_year')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('cost_variances');
        Schema::dropIfExists('costing_layer_consumptions');
        Schema::dropIfExists('costing_layers');
        Schema::dropIfExists('stock_ledger_entries');
        Schema::dropIfExists('stock_positions');
    }
};
