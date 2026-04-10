<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductBundlesTable extends Migration
{
    public function up()
    {
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('combo_product_id');
            $table->uuid('bundle_product_id');
            $table->uuid('component_product_id');
            $table->decimal('quantity', 20, 10);
            $table->uuid('component_uom_id');
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            
            // $table->foreign('combo_product_id')->references('id')->on('products');
            $table->foreign('bundle_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('component_product_id')->references('id')->on('products');
            $table->foreign('component_uom_id')->references('id')->on('uoms');
            $table->unique(['bundle_product_id', 'component_product_id'], 'bundle_component_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_bundles');
    }
}