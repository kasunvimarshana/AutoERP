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
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure');
            $table->decimal('min_quantity', 15, 4)->default(1);
            $table->decimal('price', 15, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['price_list_id', 'product_id', 'variant_id', 'uom_id', 'min_quantity'], 'pli_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};