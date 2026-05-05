<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('sales_order_id')->constrained(null, 'id', 'sales_order_lines_sales_order_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('ordered_qty', 20, 6);
            $table->decimal('shipped_qty', 20, 6)->default(0);
            $table->decimal('reserved_qty', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_pct', 10, 6)->default(0);
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('line_total', 20, 6);
            // Sales order lines income account
            $table->foreignId('income_account_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
