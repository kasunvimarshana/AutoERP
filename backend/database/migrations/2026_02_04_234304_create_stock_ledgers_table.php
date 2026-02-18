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
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            
            // Transaction details
            $table->enum('transaction_type', [
                'in',           // Stock in
                'out',          // Stock out
                'transfer_in',  // Transfer from another warehouse
                'transfer_out', // Transfer to another warehouse
                'adjustment',   // Manual adjustment
                'return',       // Return to supplier
                'damaged',      // Damaged goods
                'expired',      // Expired goods
            ]);
            
            // Quantity (always positive, direction is determined by transaction_type)
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            
            // Batch/Lot tracking
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Reference to source document
            $table->string('reference_type')->nullable(); // e.g., purchase_order, sales_order
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            
            // Transfer details (if applicable)
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');
            
            // User and notes
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            
            // Running balance (denormalized for performance)
            $table->decimal('balance_after', 15, 3);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for performance
            $table->index(['product_id', 'warehouse_id', 'created_at']);
            $table->index(['tenant_id', 'product_id', 'created_at']);
            $table->index(['batch_number']);
            $table->index(['serial_number']);
            $table->index(['reference_type', 'reference_id']);
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
