<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('sales_order_item_id')->constrained();
            $table->foreignId('stock_id')->constrained();
            $table->decimal('allocated_quantity', 15, 4);
            $table->enum('allocation_method', ['FIFO', 'CLOSEST_LOCATION', 'RANDOM', 'CUSTOM'])->default('FIFO');
            $table->enum('status', ['allocated', 'picked', 'cancelled'])->default('allocated');
            $table->timestamps();
        });
    }
};