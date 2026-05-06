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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('customer_id');
            $table->foreignId('sales_order_id')
                ->nullable()
                ->constrained('sales_orders', 'id', 'sales_invoices_sales_order_id_fk')
                ->nullOnDelete();
            $table->foreignId('shipment_id')
                ->nullable()
                ->constrained('shipments', 'id', 'sales_invoices_shipment_id_fk')
                ->nullOnDelete();
            $table->string('invoice_number');
            $table->enum('status', ['draft', 'sent', 'partial_paid', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'sales_invoices_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('discount_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->decimal('paid_amount', 20, 6)->default(0);
            $table->foreignId('ar_account_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('ar_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'invoice_number'], 'sales_invoices_tenant_invoice_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
