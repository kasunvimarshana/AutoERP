<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'supplier_products_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained(null, 'id', 'supplier_products_supplier_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id', 'supplier_products_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'supplier_products_variant_id_fk')->nullOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->decimal('min_order_qty', 20, 6)->default(1);
            $table->boolean('is_preferred')->default(false);
            $table->decimal('last_purchase_price', 20, 6)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'supplier_id', 'product_id', 'variant_id'], 'supplier_products_tenant_supplier_product_variant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
