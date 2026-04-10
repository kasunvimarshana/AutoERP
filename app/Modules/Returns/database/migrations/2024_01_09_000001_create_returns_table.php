<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('return_number')->unique();
            $table->enum('return_type', ['purchase_return', 'sales_return'])->default('sales_return');
            $table->morphs('original_order'); // PurchaseOrder, SalesOrder
            $table->foreignId('supplier_id')->nullable()->constrained();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->date('return_date');
            $table->date('received_date')->nullable();
            $table->enum('status', [
                'initiated', 'in_transit', 'received', 'qc_pending', 
                'qc_pass', 'qc_fail', 'processed', 'completed', 'cancelled'
            ])->default('initiated');
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->decimal('restocking_fee', 15, 2)->default(0);
            $table->string('reason')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['company_id', 'return_number']);
        });
    }
};