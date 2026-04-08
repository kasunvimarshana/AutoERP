<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('combo_product_id');
            $table->uuid('component_product_id');
            $table->decimal('quantity', 15, 4)->default(1);
            $table->string('unit_of_measure', 30)->nullable();
            $table->timestamps();

            $table->index(['combo_product_id']);

            $table->foreign('combo_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('component_product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combo_items');
    }
};
