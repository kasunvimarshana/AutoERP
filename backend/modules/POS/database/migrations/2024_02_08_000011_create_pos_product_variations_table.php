<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_product_variations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id'); // Link to inventory products table
            $table->string('name');
            $table->string('sub_sku')->nullable();
            $table->string('variation_value_1')->nullable(); // e.g., "Red"
            $table->string('variation_value_2')->nullable(); // e.g., "Large"
            $table->decimal('default_purchase_price', 15, 2)->nullable();
            $table->decimal('default_sell_price', 15, 2)->nullable();
            $table->string('barcode')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_product_variations');
    }
};
