<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds batch/lot/serial/expiry tracking to stock_movements and introduces the
 * stock_batches table used for FIFO/FEFO inventory valuation.
 *
 * stock_batches stores individual cost layers (one row per receipt batch).
 * FIFO selects the oldest batch first (received_at ASC).
 * FEFO selects the batch with the nearest expiry first (expiry_date ASC).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Extend stock_movements with batch/lot/serial/expiry metadata
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('variant_id');
            $table->string('lot_number')->nullable()->after('batch_number');
            $table->string('serial_number')->nullable()->after('lot_number');
            $table->date('expiry_date')->nullable()->after('serial_number');
            $table->string('valuation_method')->nullable()->after('expiry_date'); // fifo, fefo, avg
        });

        // stock_batches: append-only cost layers for FIFO/FEFO valuation
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignUuid('movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            // quantity_remaining starts equal to quantity_received and decreases as stock is consumed
            $table->decimal('quantity_received', 20, 8);
            $table->decimal('quantity_remaining', 20, 8);
            $table->decimal('cost_per_unit', 20, 8)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'warehouse_id', 'product_id', 'received_at'], 'sb_fifo_idx');
            $table->index(['tenant_id', 'warehouse_id', 'product_id', 'expiry_date'], 'sb_fefo_idx');
            $table->index(['tenant_id', 'product_id', 'quantity_remaining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'lot_number', 'serial_number', 'expiry_date', 'valuation_method']);
        });
    }
};
