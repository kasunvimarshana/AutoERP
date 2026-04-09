<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Procurement Module
     *
     * Flow (SAP MM pattern):
     *   Purchase Order (optional) → Goods Receipt (GRN) → Supplier Invoice → Payment
     *
     * SMB flexibility:
     *   - GRN without PO: goods_receipts.purchase_order_id is nullable
     *   - Direct buy: skip PO entirely
     *
     * period_id on all financial documents enforces accrual accounting.
     */
    public function up(): void
    {
        // ── Purchase Orders ───────────────────────────────────────────────────
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('po_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('status', [
                'draft', 'submitted', 'approved',
                'partially_received', 'received', 'invoiced', 'cancelled',
            ])->default('draft');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('supplier_id')->references('id')->on('parties');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'status']);
            $table->index(['supplier_id', 'order_date']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->integer('line_number');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('ordered_qty', 18, 4);
            $table->decimal('received_qty', 18, 4)->default(0);
            $table->decimal('billed_qty', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->unsignedBigInteger('account_id')->nullable(); // expense/asset GL override
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });

        // ── Goods Receipts (GRN) ──────────────────────────────────────────────
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('grn_number', 50)->unique();
            $table->unsignedBigInteger('purchase_order_id')->nullable();   // nullable = GRN without PO
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->date('receipt_date');
            $table->enum('status', ['draft', 'received', 'invoiced', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('parties');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id');
            $table->unsignedBigInteger('po_line_id')->nullable();      // nullable for GRN without PO
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();        // optional batch tracking
            $table->unsignedBigInteger('serial_id')->nullable();       // optional serial tracking
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('received_qty', 18, 4);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 4)->default(0);
            $table->unsignedBigInteger('stock_movement_id')->nullable(); // linked movement record
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->cascadeOnDelete();
            $table->foreign('po_line_id')->references('id')->on('purchase_order_lines')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serial_numbers')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
        });

        // ── Supplier Invoices (Accounts Payable) ──────────────────────────────
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('goods_receipt_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('balance_due', 18, 4)->default(0);
            $table->enum('status', ['draft', 'posted', 'partial', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('supplier_id')->references('id')->on('parties');
            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'status']);
            $table->index(['supplier_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
