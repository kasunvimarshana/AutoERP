<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->string('transaction_type'); // receipt, issue, adjustment, transfer_in, transfer_out, return
            $table->string('transaction_reference')->nullable(); // PO number, SO number, etc.
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('running_balance', 15, 2);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            // Append-only table - no updates or deletes allowed
            $table->index(['product_id', 'warehouse_id', 'transaction_date']);
            $table->index(['warehouse_id', 'transaction_date']);
            $table->index('transaction_reference');
            $table->index('batch_number');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
