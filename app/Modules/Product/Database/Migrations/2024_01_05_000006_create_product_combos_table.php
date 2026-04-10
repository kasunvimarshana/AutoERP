<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('product_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('child_product_id')->constrained('products');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
            $table->unique(['parent_product_id', 'child_product_id']);
        });
    }
};