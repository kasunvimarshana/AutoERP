<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grn_header_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number');
            $table->string('supplier_invoice_number')->nullable();
            $table->enum('status', ['draft', 'approved', 'partial_paid', 'paid', 'disputed', 'cancelled'])->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('discount_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            // Purchase invoices AP account & JE
            $table->foreignId('ap_account_id')->nullable(); // will reference accounts
            $table->foreignId('journal_entry_id')->nullable(); // will reference journal_entries
            $table->foreign('ap_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number'], 'uq_purchase_invoices_tenant_invoice');
        });

        Schema::create('purchase_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grn_line_id')->nullable()->constrained('grn_lines')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->constrained('units_of_measure');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            // Purchase invoice lines account
            $table->foreignId('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
    }
};