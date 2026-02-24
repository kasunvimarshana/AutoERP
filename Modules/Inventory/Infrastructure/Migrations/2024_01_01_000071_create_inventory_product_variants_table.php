<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();
            $table->string('sku', 100);
            $table->string('name', 255);
            $table->json('attributes')->nullable();
            $table->decimal('unit_price', 18, 8)->default(0);
            $table->decimal('cost_price', 18, 8)->default(0);
            $table->string('barcode_ean13', 13)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->foreign('product_id')->references('id')->on('inventory_products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_product_variants');
    }
};
