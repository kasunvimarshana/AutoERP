<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryBalancesTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->uuid('serial_number_id')->nullable();
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers');
            $table->decimal('quantity_on_hand', 20, 10)->default(0);
            $table->decimal('quantity_reserved', 20, 10)->default(0);
            $table->decimal('quantity_available', 20, 10)->storedAs('quantity_on_hand - quantity_reserved');
            $table->decimal('quantity_in_transit', 20, 10)->default(0);
            $table->decimal('quantity_quarantined', 20, 10)->default(0);
            $table->decimal('average_cost', 20, 6)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->unique(['product_id', 'variant_id', 'warehouse_id', 'location_id', 'batch_id'], 'unique_inventory_balance');
            // $table->unique(['product_id', 'warehouse_id', 'location_id', 'batch_id', 'serial_number_id'], 'unique_balance');
            $table->index(['product_id', 'warehouse_id', 'quantity_available']);
            $table->index(['batch_id', 'quantity_on_hand']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_balances');
    }
}