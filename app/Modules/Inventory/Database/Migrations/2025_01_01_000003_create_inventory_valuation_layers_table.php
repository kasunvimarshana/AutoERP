<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryValuationLayersTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_valuation_layers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->enum('layer_type', ['purchase', 'production', 'return', 'adjustment', 'transfer']);
            $table->decimal('quantity', 20, 10);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('remaining_quantity', 20, 10);
            $table->decimal('total_cost', 20, 6);
            $table->uuid('transaction_id');
            $table->date('layer_date');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->foreign('transaction_id')->references('id')->on('inventory_transactions');
            $table->index(['product_id', 'warehouse_id', 'layer_date']);
            $table->index(['product_id', 'remaining_quantity', 'layer_type']);
            $table->index(['expiry_date', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_valuation_layers');
    }
}