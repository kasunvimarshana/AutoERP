<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('price', 15, 2);
            $table->decimal('min_quantity', 15, 2)->nullable();
            $table->decimal('max_quantity', 15, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['price_list_id', 'product_id']);
            $table->index(['min_quantity', 'max_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
