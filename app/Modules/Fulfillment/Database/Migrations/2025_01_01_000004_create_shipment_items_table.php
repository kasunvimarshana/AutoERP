<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentItemsTable extends Migration
{
    public function up()
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id');
            $table->uuid('sales_order_item_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->decimal('quantity', 20, 10);
            $table->uuid('batch_id')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->uuid('from_location_id')->nullable();
            $table->timestamps();
            
            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->foreign('from_location_id')->references('id')->on('locations');
            $table->index(['shipment_id', 'product_id']);
            $table->index(['batch_id', 'shipment_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipment_items');
    }
}