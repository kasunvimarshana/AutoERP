<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('so_number')->unique();
            $table->uuid('customer_id');
            $table->uuid('warehouse_id');
            $table->date('order_date');
            $table->date('requested_delivery_date')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'processing', 'picking', 'packed', 'shipped', 'delivered', 'cancelled', 'on_hold']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->decimal('subtotal', 20, 6);
            $table->decimal('discount', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('shipping_cost', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->text('notes')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->index(['so_number', 'status', 'customer_id']);
            $table->index(['order_date', 'requested_delivery_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_orders');
    }
}