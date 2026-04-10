<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('order_type');
            $table->uuid('sales_order_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->uuid('serial_number_id')->nullable();
            $table->decimal('quantity', 20, 10);
            $table->decimal('allocated_quantity', 20, 10)->default(0);
            $table->enum('status', ['pending', 'allocated', 'partially_shipped', 'shipped', 'cancelled', 'expired']);
            $table->timestamp('expires_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('sales_order_id')->references('id')->on('sales_orders');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers');
            $table->index(['order_id', 'order_type', 'status']);
            $table->index(['product_id', 'warehouse_id', 'expires_at']);
            $table->index(['batch_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_reservations');
    }
}