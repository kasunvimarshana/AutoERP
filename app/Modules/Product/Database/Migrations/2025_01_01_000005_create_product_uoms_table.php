<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductUomsTable extends Migration
{
    public function up()
    {
        Schema::create('product_uoms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('uom_id');
            $table->decimal('conversion_factor', 20, 10);
            $table->decimal('purchase_conversion', 20, 10)->nullable();
            $table->decimal('sales_conversion', 20, 10)->nullable();
            $table->decimal('inventory_conversion', 20, 10)->nullable();
            $table->boolean('is_purchase_uom')->default(false);
            $table->boolean('is_sales_uom')->default(false);
            $table->boolean('is_inventory_uom')->default(false);
            $table->decimal('purchase_price', 20, 6)->nullable();
            $table->decimal('sales_price', 20, 6)->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->unique(['product_id', 'uom_id']);
            $table->index(['product_id', 'is_purchase_uom', 'is_sales_uom']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_uoms');
    }
}