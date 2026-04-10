<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentsTable extends Migration
{
    public function up()
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shipment_number')->unique();
            $table->uuid('sales_order_id');
            $table->uuid('warehouse_id');
            $table->date('shipment_date');
            $table->enum('status', ['pending', 'picking', 'packed', 'shipped', 'delivered', 'cancelled']);
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->json('shipping_method')->nullable();
            $table->decimal('weight', 20, 6)->nullable();
            $table->decimal('shipping_cost', 20, 6)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('sales_order_id')->references('id')->on('sales_orders');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->index(['shipment_number', 'status', 'sales_order_id']);
            $table->index(['tracking_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipments');
    }
}