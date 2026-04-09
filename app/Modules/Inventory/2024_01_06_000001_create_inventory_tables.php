<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Batches / Lots ────────────────────────────────────────────────────
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('batch_number', 100)->unique();
            $table->string('lot_number', 100)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('supplier_batch', 100)->nullable(); // supplier's batch ref
            $table->enum('status', ['active', 'quarantine', 'expired', 'recalled', 'disposed'])->default('active');
            $table->json('attributes')->nullable();             // temperature, pH, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();

            $table->index(['tenant_id', 'status']);
            $table->index(['product_id', 'expiry_date']);
        });

        // ── Serial Numbers ────────────────────────────────────────────────────
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('serial_number', 150)->unique();
            $table->enum('status', ['in_stock', 'sold', 'returned', 'defective', 'disposed'])->default('in_stock');
            $table->unsignedBigInteger('current_location_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('current_location_id')->references('id')->on('locations')->nullOnDelete();

            $table->index(['tenant_id', 'status']);
            $table->index(['product_id', 'status']);
        });

        // ── Stock Balances (real-time snapshot) ───────────────────────────────
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('qty_on_hand', 18, 4)->default(0);
            $table->decimal('qty_reserved', 18, 4)->default(0);
            $table->decimal('qty_available', 18, 4)->default(0);
            $table->decimal('qty_incoming', 18, 4)->default(0);
            $table->decimal('avg_cost', 18, 4)->default(0);         // WAC running average
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('uom_id')->references('id')->on('units_of_measure');

            // Composite unique key for balance lookup
            $table->unique(['product_id', 'variant_id', 'batch_id', 'location_id', 'uom_id'], 'stock_balance_unique');
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'location_id']);
        });

        // ── Stock Movements (immutable ledger) ────────────────────────────────
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('movement_number', 50)->unique();
            $table->enum('movement_type', [
                'receipt',       // goods received (inbound)
                'issue',         // goods issued (outbound)
                'transfer',      // location-to-location
                'adjustment',    // inventory correction
                'return',        // goods returned
                'cycle_count',   // count variance posted
                'disposal',      // write-off / scrap
                'reservation',   // reserved (no physical move)
            ]);
            $table->string('source_type', 100);   // polymorphic: GoodsReceipt, DeliveryOrder, etc.
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 4)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable(); // linked GL entry
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serial_numbers')->nullOnDelete();
            $table->foreign('from_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('to_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'movement_type']);
            $table->index(['source_type', 'source_id']);
            $table->index(['product_id', 'moved_at']);
        });

        // ── Inventory Layers (FIFO/LIFO/FEFO cost layers) ─────────────────────
        Schema::create('inventory_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->date('receipt_date');
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('qty_received', 18, 4);
            $table->decimal('qty_remaining', 18, 4);  // decremented on consumption
            $table->enum('method', ['FIFO', 'LIFO', 'FEFO', 'WAC', 'SPECIFIC']);
            $table->string('source_type', 100);   // polymorphic
            $table->unsignedBigInteger('source_id');
            $table->boolean('is_exhausted')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations');

            $table->index(['product_id', 'is_exhausted', 'receipt_date']);
            $table->index(['product_id', 'batch_id', 'location_id']);
        });

        // ── Stock Reservations ────────────────────────────────────────────────
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 18, 4);
            $table->string('reserved_for_type', 100);  // polymorphic: SalesOrder, etc.
            $table->unsignedBigInteger('reserved_for_id');
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'fulfilled', 'cancelled', 'expired'])->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations');

            $table->index(['product_id', 'status']);
            $table->index(['reserved_for_type', 'reserved_for_id']);
        });

        // ── Cycle Counts ──────────────────────────────────────────────────────
        Schema::create('cycle_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('reference', 50)->unique();
            $table->enum('status', ['draft', 'in_progress', 'review', 'completed', 'cancelled'])->default('draft');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('cycle_count_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_count_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->decimal('expected_qty', 18, 4);
            $table->decimal('counted_qty', 18, 4)->nullable();
            $table->decimal('variance_qty', 18, 4)->nullable();
            $table->enum('status', ['pending', 'counted', 'adjusted'])->default('pending');
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->foreign('cycle_count_id')->references('id')->on('cycle_counts')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('counted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
        Schema::dropIfExists('cycle_counts');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('inventory_layers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('batches');
    }
};
