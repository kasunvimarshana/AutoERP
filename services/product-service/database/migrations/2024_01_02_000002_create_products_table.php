<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Tenant isolation
            $table->unsignedBigInteger('tenant_id')->index();

            // Category (nullable – product may be uncategorised)
            $table->unsignedBigInteger('category_id')->nullable()->index();

            // Core fields
            $table->string('sku', 100)->index();
            $table->string('name');
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price',      15, 4)->default(0);
            $table->decimal('cost_price', 15, 4)->default(0);

            // Physical attributes
            $table->string('unit', 50)->nullable();
            $table->decimal('weight', 10, 4)->nullable();
            $table->json('dimensions')->nullable();

            // Media & metadata
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();

            // Status
            $table->boolean('is_active')->default(true)->index();

            // Stock thresholds (actual stock quantities live in inventory-service)
            $table->unsignedInteger('min_stock_level')->nullable();
            $table->unsignedInteger('max_stock_level')->nullable();
            $table->unsignedInteger('reorder_point')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite unique: SKU per tenant
            $table->unique(['tenant_id', 'sku']);

            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
