<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->string('item_description')->nullable();
            $table->integer('line_number')->default(1);
            $table->decimal('quantity', 15, 2);
            $table->string('uom', 50)->nullable();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->decimal('quantity_shipped', 15, 2)->default(0);
            $table->decimal('quantity_remaining', 15, 2);
            $table->string('fulfillment_status', 50)->default('pending'); // pending, partial, fulfilled, cancelled
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->index(['sales_order_id', 'line_number']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_line_items');
    }
};
