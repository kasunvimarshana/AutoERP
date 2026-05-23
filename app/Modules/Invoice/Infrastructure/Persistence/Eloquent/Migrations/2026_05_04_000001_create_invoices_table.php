<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('direction')->default('inbound')->comment('inbound, outbound');
            $table->string('invoice_type'); // purchase, sale, vehicle_service, vehicle_rental_lessor, vehicle_rental_lessee
            $table->string('invoice_number');
            $table->string('reference')->nullable();
            $table->string('status')->default('draft')->comment('draft, approved, partial_paid, paid, disputed, cancelled');
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);

            // Line-derived totals - strictly SUM over lines
            $table->decimal('subtotal', 20, 4)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_tax_total', 20, 4)->default(0)->comment('SUM(line.tax_amount)');
            $table->decimal('line_discount_total', 20, 4)->default(0)->comment('SUM(line.discount_amount)');

            // Header-level adjustments applied on top of the document
            $table->string('header_discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('header_discount_value', 20, 4)->nullable();
            $table->decimal('header_discount_amount', 20, 4)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 4)->default(0);

            // Final totals combine line rollups and header adjustments
            $table->decimal('discount_total', 20, 4)->storedAs('line_discount_total + header_discount_amount')->comment('line_discount_total + header_discount_amount');
            $table->decimal('tax_total', 20, 4)->storedAs('line_tax_total + header_tax_amount')->comment('line_tax_total + header_tax_amount');
            $table->decimal('debit_note_total', 20, 4)->default(0)->comment('SUM of debit notes');
            $table->decimal('credit_note_total', 20, 4)->default(0)->comment('SUM of credit notes');
            $table->decimal('grand_total', 20, 4)->storedAs('subtotal - line_discount_total - header_discount_amount + line_tax_total + header_tax_amount + debit_note_total - credit_note_total')->comment('subtotal - discount_total + tax_total + debit_note_total - credit_note_total');

            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('balance', 20, 4)->storedAs('subtotal - line_discount_total - header_discount_amount + line_tax_total + header_tax_amount + debit_note_total - credit_note_total - paid_amount')->comment('grand_total - paid_amount');
            $table->foreignId('ap_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('ar_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();


            $table->unique(['tenant_id', 'invoice_number'], 'invoices_invoice_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
