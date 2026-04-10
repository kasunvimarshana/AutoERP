<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('inventory_valuation_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('initial_quantity', 15, 4);
            $table->decimal('remaining_quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_cost', 15, 4)->virtualAs('remaining_quantity * unit_cost');
            $table->morphs('source'); // PurchaseOrderItem, ReturnItem
            $table->enum('method', ['FIFO', 'LIFO', 'AVCO', 'WAC'])->default('FIFO');
            $table->enum('status', ['active', 'consumed', 'reserved'])->default('active');
            $table->date('created_at_date');
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'status']);
        });
    }
};