<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('sales_order_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('product_name', 300)->nullable();
            $table->string('unit_of_measure', 30)->default('piece');
            $table->decimal('quantity_ordered', 15, 4);
            $table->decimal('quantity_shipped', 15, 4)->default(0);
            $table->decimal('quantity_returned', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sales_order_id']);
            $table->index(['product_id']);

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
