<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->decimal('min_order_qty', 15, 4)->default(1);
            $table->boolean('is_preferred')->default(false);
            $table->decimal('last_purchase_price', 15, 4)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'supplier_id', 'product_id', 'variant_id'], 'supplier_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};