<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales Module
     *
     * Flow (SAP SD pattern):
     *   Sales Order (optional) → Delivery Order → Customer Invoice → Payment
     *
     * SMB flexibility:
     *   - Direct sale: skip SO, create invoice directly
     *   - delivery_orders.sales_order_id nullable for direct invoice
     *
     * Payments handle both inbound (customer receipts) and outbound (supplier payments)
     * via the direction field.
     */
    public function up(): void
    {
        // ── Sales Orders ──────────────────────────────────────────────────────
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('so_number', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('status', [
                'draft', 'confirmed', 'picking', 'shipped', 'invoiced', 'cancelled',
            ])->default('draft');
            $table->date('order_date');
            $table->date('requested_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('customer_id')->references('id')->on('parties');
            $table->foreign('price_list_id')->references('id')->on('price_lists')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->nullOnDelete();
            $table->foreign('billing_address_id')->references('id')->on('party_addresses')->nullOnDelete();
            $table->foreign('shipping_address_id')->references('id')->on('party_addresses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'order_date']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id');
            $table->integer('line_number');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('ordered_qty', 18, 4);
            $table->decimal('shipped_qty', 18, 4)->default(0);
            $table->decimal('invoiced_qty', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
        });

        // ── Delivery Orders ───────────────────────────────────────────────────
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('delivery_number', 50)->unique();
            $table->unsignedBigInteger('sales_order_id')->nullable(); // nullable = direct delivery
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('status', ['draft', 'picked', 'packed', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->date('ship_date')->nullable();
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('delivery_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->unsignedBigInteger('so_line_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('from_location_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('delivered_qty', 18, 4);
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->timestamps();

            $table->foreign('delivery_order_id')->references('id')->on('delivery_orders')->cascadeOnDelete();
            $table->foreign('so_line_id')->references('id')->on('sales_order_lines')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serial_numbers')->nullOnDelete();
            $table->foreign('from_location_id')->references('id')->on('locations');
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
        });

        // ── Customer Invoices (Accounts Receivable) ────────────────────────────
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->unsignedBigInteger('delivery_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('balance_due', 18, 4)->default(0);
            $table->enum('status', ['draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('customer_id')->references('id')->on('parties');
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->nullOnDelete();
            $table->foreign('delivery_id')->references('id')->on('delivery_orders')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'due_date']);
        });

        // ── Payments (inbound + outbound) ──────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('payment_number', 50)->unique();
            $table->enum('direction', ['inbound', 'outbound']); // inbound=receipt; outbound=disbursement
            $table->unsignedBigInteger('party_id');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('payment_date');
            $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'card', 'credit_note', 'other']);
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('amount', 18, 4);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'bounced', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('party_id')->references('id')->on('parties');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'direction', 'status']);
            $table->index(['party_id', 'payment_date']);
        });

        // ── Payment Allocations (many-to-many: payment ↔ invoice) ─────────────
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            // Polymorphic: customer_invoice | supplier_invoice | credit_note
            $table->enum('invoice_type', ['customer_invoice', 'supplier_invoice', 'credit_note']);
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('allocated_amount', 18, 4);
            $table->timestamp('allocated_at')->useCurrent();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();

            $table->index(['payment_id']);
            $table->index(['invoice_type', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('customer_invoices');
        Schema::dropIfExists('delivery_lines');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
