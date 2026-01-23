<?php

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
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('part_number')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('item_type', ['part', 'consumable', 'service', 'labor', 'dummy'])->default('part');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('unit_of_measure')->default('piece'); // piece, liter, kg, etc.
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('markup_percentage', 5, 2)->nullable();
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('minimum_stock_level')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->string('location')->nullable(); // Warehouse location
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dummy')->default(false); // For dummy/virtual items
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'item_type']);
            $table->index('sku');
            $table->index('part_number');
            $table->index(['category', 'is_active']);
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
