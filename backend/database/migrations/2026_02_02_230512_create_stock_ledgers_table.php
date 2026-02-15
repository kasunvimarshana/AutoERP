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
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            
            // Transaction Details
            $table->enum('transaction_type', ['purchase', 'sale', 'transfer_in', 'transfer_out', 'adjustment_in', 'adjustment_out', 'return', 'production', 'consumption'])->index();
            $table->string('reference_type')->nullable(); // e.g., PurchaseOrder, SalesOrder, Transfer
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->date('transaction_date')->index();
            
            // Quantity Tracking
            $table->decimal('quantity', 15, 4); // Can be positive (IN) or negative (OUT)
            $table->decimal('balance_quantity', 15, 4); // Running balance
            $table->foreignId('unit_id')->constrained('units_of_measure');
            
            // Batch/Serial/Lot Tracking
            $table->string('batch_number')->nullable()->index();
            $table->string('serial_number')->nullable()->index();
            $table->string('lot_number')->nullable()->index();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            
            // Cost Tracking
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->enum('valuation_method', ['fifo', 'fefo', 'lifo', 'average'])->default('fifo');
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tenant_id', 'product_id', 'branch_id']);
            $table->index(['tenant_id', 'product_id', 'transaction_date']);
            $table->index(['tenant_id', 'branch_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
            
            // Append-only constraint - no updates or deletes allowed
            // This is enforced at application level
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
