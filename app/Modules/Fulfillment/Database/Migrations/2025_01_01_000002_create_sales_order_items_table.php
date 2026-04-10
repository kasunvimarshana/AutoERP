<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sales_order_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->decimal('quantity', 20, 10);
            $table->decimal('allocated_quantity', 20, 10)->default(0);
            $table->decimal('shipped_quantity', 20, 10)->default(0);
            $table->uuid('uom_id');
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount', 20, 6)->default(0);
            $table->decimal('tax_rate', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_price', 20, 6);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->index(['sales_order_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_order_items');
    }
}