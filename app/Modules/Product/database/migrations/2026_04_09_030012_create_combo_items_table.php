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
            $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('child_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('child_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->foreignId('uom_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->unique(['parent_product_id', 'child_product_id', 'child_variant_id', 'uom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
