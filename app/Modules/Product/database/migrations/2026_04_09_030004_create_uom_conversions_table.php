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
            $table->foreignId('from_uom_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('to_uom_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->decimal('factor', 20, 8);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unique(['from_uom_id', 'to_uom_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
