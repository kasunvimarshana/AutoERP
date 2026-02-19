<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('item_code')->comment('SKU or item code');
            $table->string('item_name');
            $table->string('category')->nullable()->comment('e.g., Parts, Oil, Accessories');
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->default('EA')->comment('EA, L, KG, etc.');
            $table->integer('reorder_level')->default(0)->comment('Minimum quantity before reorder alert');
            $table->integer('reorder_quantity')->default(0)->comment('Default quantity to reorder');
            $table->decimal('unit_cost', 10, 2)->default(0)->comment('Cost price');
            $table->decimal('selling_price', 10, 2)->default(0)->comment('Selling price');
            $table->integer('stock_on_hand')->default(0)->comment('Current stock quantity');
            $table->boolean('is_dummy_item')->default(false)->comment('True for packaged services without physical stock');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one item_code per branch
            $table->unique(['branch_id', 'item_code']);

            // Indexes for performance
            $table->index('item_code');
            $table->index('item_name');
            $table->index('category');
            $table->index('stock_on_hand');
            $table->index('is_dummy_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
