<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->decimal('factor', 20, 8);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->timestamps();

            $table->foreign('from_uom_id')->references('id')->on('units_of_measure')->cascadeOnDelete();
            $table->foreign('to_uom_id')->references('id')->on('units_of_measure')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();

            $table->unique(['from_uom_id', 'to_uom_id', 'product_id'], 'unique_uom_conversion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};