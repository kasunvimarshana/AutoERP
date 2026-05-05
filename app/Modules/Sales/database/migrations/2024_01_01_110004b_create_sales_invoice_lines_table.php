<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('sales_invoice_id')->constrained(null, 'id', 'sales_invoice_lines_sales_invoice_id_fk')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines', 'id', 'sales_invoice_lines_sales_order_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_pct', 10, 6)->default(0);
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('line_total', 20, 6);
            $table->foreignId('income_account_id')->nullable();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->nullOnDelete();
            $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
    }
};
