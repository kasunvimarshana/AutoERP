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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete()->comment('Branch isolation');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();

            // Basic Information
            $table->string('sku')->comment('Stock Keeping Unit / Product Code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('barcode')->nullable();
            $table->enum('type', ['goods', 'services', 'digital', 'bundle', 'composite'])->default('goods');
            $table->enum('status', ['active', 'inactive', 'discontinued', 'out_of_stock'])->default('active');

            // Units
            $table->foreignId('buy_unit_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()->comment('Unit for purchasing');
            $table->foreignId('sell_unit_id')->nullable()->constrained('unit_of_measures')->nullOnDelete()->comment('Unit for selling');

            // Pricing
            $table->decimal('cost_price', 15, 2)->default(0)->comment('Purchase/Cost price');
            $table->decimal('selling_price', 15, 2)->default(0)->comment('Default selling price');
            $table->decimal('min_price', 15, 2)->nullable()->comment('Minimum allowed selling price');
            $table->decimal('max_price', 15, 2)->nullable()->comment('Maximum allowed selling price');

            // Inventory
            $table->boolean('track_inventory')->default(true)->comment('Enable inventory tracking');
            $table->integer('current_stock')->default(0)->comment('Current stock level');
            $table->integer('reorder_level')->default(0)->comment('Stock level to trigger reorder');
            $table->integer('reorder_quantity')->default(0)->comment('Quantity to order when reordering');
            $table->integer('min_stock_level')->default(0);
            $table->integer('max_stock_level')->nullable();

            // Additional Fields
            $table->json('attributes')->nullable()->comment('Product specifications/attributes (JSON)');
            $table->json('images')->nullable()->comment('Product images (JSON array of paths)');
            $table->string('manufacturer')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('weight', 10, 3)->nullable()->comment('Product weight');
            $table->string('weight_unit')->nullable()->default('kg');
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('dimension_unit')->nullable()->default('cm');

            // Tax & Discounts
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 5, 2)->nullable()->comment('Tax percentage');
            $table->boolean('allow_discount')->default(true);
            $table->decimal('max_discount_percentage', 5, 2)->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per branch
            $table->unique(['branch_id', 'sku']);

            // Indexes for performance
            $table->index('sku');
            $table->index('name');
            $table->index('barcode');
            $table->index('type');
            $table->index('status');
            $table->index('category_id');
            $table->index('manufacturer');
            $table->index('brand');
            $table->index('track_inventory');
            $table->index('current_stock');
            $table->index('is_featured');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
