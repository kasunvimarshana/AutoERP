<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'purchase_invoices_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id');
            $table->foreignId('grn_header_id')->nullable()->constrained(null, 'id', 'purchase_invoices_grn_header_id_fk')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained(null, 'id', 'purchase_invoices_purchase_order_id_fk')->nullOnDelete();
            $table->string('invoice_number');
            $table->string('supplier_invoice_number')->nullable();
            $table->enum('status', ['draft', 'approved', 'partial_paid', 'paid', 'disputed', 'cancelled'])->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'purchase_invoices_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('discount_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->decimal('paid_amount', 20, 6)->default(0);
            $table->foreignId('ap_account_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('ap_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number'], 'purchase_invoices_tenant_invoice_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
