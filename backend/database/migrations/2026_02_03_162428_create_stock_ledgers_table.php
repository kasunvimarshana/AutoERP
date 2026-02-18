<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Movement details
            $table->string('movement_type'); // purchase, sale, adjustment, transfer, etc.
            $table->decimal('quantity', 15, 4); // Positive for increase, negative for decrease
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();

            // Location tracking
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');

            // Batch/Lot/Serial tracking
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Reference to source document
            $table->string('reference_type')->nullable(); // PurchaseOrder, SalesOrder, etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            // User who created the entry
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            // Notes and additional data
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Running balance (for quick queries)
            $table->decimal('running_balance', 15, 4)->default(0);

            $table->timestamp('transaction_date');
            $table->timestamps();

            // NO soft deletes - this is an append-only ledger

            // Indexes for performance
            $table->index(['tenant_id', 'product_id', 'transaction_date']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['batch_number']);
            $table->index(['serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
