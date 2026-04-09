<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supplier Invoice Lines — detailed line items for AP invoices.
     * Linked back to GRN lines for 3-way matching:
     *   Purchase Order → GRN → Supplier Invoice (3-way match)
     */
    public function up(): void
    {
        Schema::create('supplier_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_invoice_id');
            $table->unsignedBigInteger('grn_line_id')->nullable();       // 3-way match reference
            $table->unsignedBigInteger('po_line_id')->nullable();
            $table->integer('line_number');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->unsignedBigInteger('account_id')->nullable();   // expense GL account
            $table->timestamps();

            $table->foreign('supplier_invoice_id')->references('id')->on('supplier_invoices')->cascadeOnDelete();
            $table->foreign('grn_line_id')->references('id')->on('goods_receipt_lines')->nullOnDelete();
            $table->foreign('po_line_id')->references('id')->on('purchase_order_lines')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });

        // ── Customer Invoice Lines ─────────────────────────────────────────────
        Schema::create('customer_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_invoice_id');
            $table->unsignedBigInteger('so_line_id')->nullable();
            $table->unsignedBigInteger('delivery_line_id')->nullable();
            $table->integer('line_number');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->unsignedBigInteger('account_id')->nullable();   // revenue GL account
            $table->timestamps();

            $table->foreign('customer_invoice_id')->references('id')->on('customer_invoices')->cascadeOnDelete();
            $table->foreign('so_line_id')->references('id')->on('sales_order_lines')->nullOnDelete();
            $table->foreign('delivery_line_id')->references('id')->on('delivery_lines')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_lines');
        Schema::dropIfExists('supplier_invoice_lines');
    }
};
