<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'sales_invoices_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('customer_id');
            $table->foreignId('sales_order_id')->nullable()->constrained(null, 'id', 'sales_invoices_sales_order_id_fk')->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained(null, 'id', 'sales_invoices_shipment_id_fk')->nullOnDelete();
            $table->string('invoice_number');
            $table->enum('status', ['draft', 'sent', 'partial_paid', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'sales_invoices_currency_id_fk');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('discount_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            // Sales invoices AR account & JE
            $table->foreignId('ar_account_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number'], 'sales_invoices_tenant_invoice_uk');
        });

        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained(null, 'id', 'sales_invoice_lines_sales_invoice_id_fk')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines', 'id', 'sales_invoice_lines_sales_order_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            // Sales invoice lines income account
            $table->foreignId('income_account_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
        Schema::dropIfExists('sales_invoices');
    }
};
