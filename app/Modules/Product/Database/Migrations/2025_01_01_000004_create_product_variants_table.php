<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsTable extends Migration
{
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('attribute_id');
            $table->string('value');
            $table->string('sku')->nullable()->unique();
            $table->string('gtin', 14)->nullable()->index();
            $table->string('name');
            $table->json('attribute_values');
            $table->json('attributes'); // {"size":"M","color":"Red"}
            $table->decimal('extra_cost', 15, 4)->default(0);
            $table->decimal('additional_price', 20, 6)->default(0);
            $table->decimal('additional_weight', 20, 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('attribute_id')->references('id')->on('product_attributes');
            $table->index(['product_id', 'sku']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
}