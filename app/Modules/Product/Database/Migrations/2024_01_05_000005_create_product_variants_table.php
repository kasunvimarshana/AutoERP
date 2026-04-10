<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name');
            $table->json('attribute_combinations'); // {color: red, size: L}
            $table->decimal('standard_cost', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};