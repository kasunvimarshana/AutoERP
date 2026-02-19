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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete()->comment('Branch isolation');

            // Variant Information
            $table->string('sku')->comment('Variant-specific SKU');
            $table->string('name')->comment('Variant name (e.g., "Red - Large")');
            $table->string('barcode')->nullable();

            // Variant Attributes
            $table->json('variant_attributes')->nullable()->comment('Attributes like color, size, etc. (JSON)');

            // Pricing (overrides product pricing)
            $table->decimal('cost_price', 15, 2)->nullable()->comment('Override cost price');
            $table->decimal('selling_price', 15, 2)->nullable()->comment('Override selling price');

            // Inventory
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->nullable();
            $table->integer('reorder_quantity')->nullable();

            // Additional
            $table->json('images')->nullable()->comment('Variant-specific images');
            $table->decimal('weight', 10, 3)->nullable();
            $table->boolean('is_default')->default(false)->comment('Is this the default variant?');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per branch
            $table->unique(['branch_id', 'sku']);

            // Indexes
            $table->index('product_id');
            $table->index('sku');
            $table->index('name');
            $table->index('barcode');
            $table->index('is_default');
            $table->index('is_active');
            $table->index('current_stock');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
