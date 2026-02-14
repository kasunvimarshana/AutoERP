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
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->enum('transaction_type', ['purchase', 'sale', 'transfer_in', 'transfer_out', 'adjustment_in', 'adjustment_out', 'return', 'production']);
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['tenant_id', 'product_id', 'warehouse_id']);
            $table->index(['batch_number']);
            $table->index(['serial_number']);
            $table->index(['expiry_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};
