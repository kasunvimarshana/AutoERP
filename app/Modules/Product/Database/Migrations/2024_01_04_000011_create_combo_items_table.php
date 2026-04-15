<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_product_id');
            $table->unsignedBigInteger('child_product_id');
            $table->unsignedBigInteger('child_variant_id')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->unsignedBigInteger('uom_id');
            $table->timestamps();

            $table->foreign('parent_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('child_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('child_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure')->cascadeOnDelete();

            $table->unique(['parent_product_id', 'child_product_id', 'child_variant_id'], 'unique_combo_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};