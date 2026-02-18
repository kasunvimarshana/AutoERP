<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('item_description')->nullable();
            $table->integer('line_number')->default(1);
            $table->decimal('quantity', 15, 2);
            $table->string('uom', 50)->nullable();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->decimal('quantity_received', 15, 2)->default(0);
            $table->decimal('quantity_remaining', 15, 2);
            $table->string('receipt_status', 50)->default('pending'); // pending, partial, received, cancelled
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'line_number']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_line_items');
    }
};
