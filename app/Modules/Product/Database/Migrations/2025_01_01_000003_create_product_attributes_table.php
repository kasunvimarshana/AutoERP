<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductAttributesTable extends Migration
{
    public function up()
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('product_id');
            $table->string('name');
            $table->enum('type', ['text', 'select', 'number']);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // $table->index(['product_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attributes');
    }
}