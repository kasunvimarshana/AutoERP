<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Returns Management Module
     *
     * Supports ALL return scenarios:
     *   - Sales Return (from_customer): customer sends goods back
     *   - Purchase Return (to_supplier): we send goods back to supplier
     *   - Partial returns (quantity < original)
     *   - Returns WITH original batch/lot/serial reference
     *   - Returns WITHOUT original batch/lot/serial reference
     *   - Condition-based handling: good, damaged, expired, other
     *   - Restock actions: restock, quarantine, dispose, return_to_vendor
     *   - Restocking fees
     *   - Credit note generation
     *   - Quality check notes
     *
     * original_order_type + original_order_id = polymorphic reference to
     * the originating sales_order, purchase_order, customer_invoice, or supplier_invoice.
     */
    public function up(): void
    {
        // ── Return Orders ─────────────────────────────────────────────────────
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('return_number', 50)->unique();
            $table->enum('direction', ['from_customer', 'to_supplier']);
            $table->unsignedBigInteger('party_id');

            // Polymorphic link to originating document (nullable for no-reference returns)
            $table->enum('original_order_type', [
                'sales_order',
                'purchase_order',
                'customer_invoice',
                'supplier_invoice',
            ])->nullable();
            $table->unsignedBigInteger('original_order_id')->nullable();

            $table->unsignedBigInteger('warehouse_id');
            $table->date('return_date');
            $table->string('reason', 255)->nullable();
            $table->enum('status', [
                'draft', 'approved', 'received', 'inspected', 'completed', 'cancelled',
            ])->default('draft');
            $table->enum('restock_action', [
                'restock',          // put back into sellable stock
                'quarantine',       // move to quarantine location
                'dispose',          // write off / scrap
                'return_to_vendor', // send back to supplier
            ])->default('restock');
            $table->decimal('restocking_fee', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->unsignedBigInteger('credit_note_id')->nullable();      // generated credit note
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('party_id')->references('id')->on('parties');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'direction', 'status']);
            $table->index(['original_order_type', 'original_order_id']);
            $table->index(['party_id', 'return_date']);
        });

        // ── Return Order Lines ────────────────────────────────────────────────
        Schema::create('return_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_order_id');

            // Nullable: allows returns without referencing original line
            $table->unsignedBigInteger('original_line_id')->nullable();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();

            // Batch/serial are nullable: supports returns without original traceability
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();

            $table->unsignedBigInteger('location_id')->nullable();   // destination location after inspection
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4)->default(0);

            // Condition-based handling
            $table->enum('condition', ['good', 'damaged', 'expired', 'other'])->default('good');
            $table->enum('restock_action', ['restock', 'quarantine', 'dispose'])->default('restock');
            $table->text('quality_check_notes')->nullable();

            $table->unsignedBigInteger('stock_movement_id')->nullable(); // generated movement
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('total', 18, 4)->default(0);
            $table->timestamps();

            $table->foreign('return_order_id')->references('id')->on('return_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serial_numbers')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
        });

        // ── Credit Notes ──────────────────────────────────────────────────────
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('cn_number', 50)->unique();
            $table->enum('direction', [
                'issued_to_customer',       // sales return → credit customer
                'received_from_supplier',   // purchase return → supplier credits us
            ]);
            $table->unsignedBigInteger('party_id');
            $table->unsignedBigInteger('return_order_id')->nullable();
            $table->date('issue_date');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('amount', 18, 4);
            $table->decimal('remaining_amount', 18, 4);   // decremented as credit is applied
            $table->enum('status', ['open', 'partial', 'applied', 'cancelled'])->default('open');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('party_id')->references('id')->on('parties');
            $table->foreign('return_order_id')->references('id')->on('return_orders')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['tenant_id', 'direction', 'status']);
            $table->index(['party_id', 'issue_date']);
        });

        // Back-fill the FK now that credit_notes exists
        Schema::table('return_orders', function (Blueprint $table) {
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            $table->dropForeign(['credit_note_id']);
        });
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('return_order_lines');
        Schema::dropIfExists('return_orders');
    }
};
