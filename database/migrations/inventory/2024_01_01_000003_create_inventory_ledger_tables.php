<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Warehouse Bins
        Schema::create('bins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained()->cascadeOnDelete(); // Location is the Warehouse
            $table->string('name')->index(); // e.g., A-01-B2
            $table->string('code')->index();
            $table->enum('type', ['STORAGE', 'RECEIVING', 'SHIPPING', 'DAMAGE', 'RETURN'])->default('STORAGE');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Inventory Ledger (Immutable Stock Transactions)
        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained()->cascadeOnDelete(); // Warehouse
            $table->foreignUuid('bin_id')->nullable()->constrained(); // Storage location
            $table->foreignUuid('product_id')->constrained();
            $table->enum('movement_type', ['IN', 'OUT', 'ADJUST', 'TRANSFER', 'RESERVATION', 'RESERVATION_RELEASE'])->index();
            $table->decimal('quantity', 20, 4); // Negative for OUT
            $table->foreignUuid('uom_id')->constrained('uoms'); // UOM used for the movement
            $table->decimal('base_quantity', 20, 4); // Normalized to base UOM
            $table->decimal('unit_cost', 20, 4)->default(0); // Cost at the time of movement
            $table->decimal('total_valuation', 20, 4)->default(0);
            $table->string('reference_id')->index(); // Order ID, GRN ID, etc.
            $table->string('reference_type')->index();
            $table->string('batch_id')->nullable()->index();
            $table->json('serial_numbers')->nullable();
            $table->foreignUuid('user_id')->constrained(); // Who did the movement
            $table->timestamp('created_at')->index();
            $table->json('metadata')->nullable();

            // Index for fast history reconstruction
            $table->index(['tenant_id', 'product_id', 'location_id', 'created_at']);
        });

        // 3. Stock Snapshot (Real-time View)
        Schema::create('stock_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('bin_id')->nullable()->constrained();
            $table->foreignUuid('product_id')->constrained();
            $table->string('batch_id')->nullable()->index();
            $table->decimal('on_hand_qty', 20, 4)->default(0);
            $table->decimal('reserved_qty', 20, 4)->default(0);
            $table->decimal('available_qty', 20, 4)->storedAs('on_hand_qty - reserved_qty');
            $table->decimal('valuation_avg', 20, 4)->default(0); // Weighted average cost
            $table->timestamp('updated_at');

            $table->unique(['tenant_id', 'location_id', 'bin_id', 'product_id', 'batch_id'], 'unique_stock_index');
        });

        // 4. Batches/Lots
        Schema::create('batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->index();
            $table->foreignUuid('product_id')->constrained();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_quarantined')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
        Schema::dropIfExists('stock_snapshots');
        Schema::dropIfExists('inventory_ledger');
        Schema::dropIfExists('bins');
    }
};
