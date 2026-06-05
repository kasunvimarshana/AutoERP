<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();

            $table->string('invoice_number', 100);
            $table->string('external_reference_number', 100)->nullable();

            $table->string('document_type', 50)->default('invoice');
            // invoice, debit_adjustment, credit_adjustment, refund, reversal, write_off

            $table->string('business_context', 50)->default('manual');
            // sales, purchase, vehicle_service, manual

            $table->string('ledger_direction', 30);
            // receivable, payable

            $table->string('balance_effect', 30);
            // increase, decrease, none

            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->string('status', 50)->default('draft');
            // draft, approved, posted, partially_settled, settled, cancelled, reversed

            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('reversal_of_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('reason_code', 100)->nullable();
            $table->text('reason')->nullable();

            // Monetary values are non-negative magnitudes. balance_effect defines the direction.
            $table->decimal('gross_total', 20, 4)->default(0);
            $table->decimal('line_discount_total', 20, 4)->default(0);
            $table->decimal('header_discount_total', 20, 4)->default(0);
            $table->decimal('taxable_total', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('charge_total', 20, 4)->default(0);
            $table->decimal('rounding_adjustment', 20, 4)->default(0);
            $table->decimal('debit_adjustment_total', 20, 4)->default(0);
            $table->decimal('credit_adjustment_total', 20, 4)->default(0);
            $table->decimal('refund_total', 20, 4)->default(0);
            $table->decimal('write_off_total', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->decimal('settled_total', 20, 4)->default(0);
            $table->decimal('balance_total', 20, 4)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->unsignedBigInteger('row_version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'organization_unit_id']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'supplier_id']);
            $table->index(['tenant_id', 'document_type', 'status']);
            $table->index(['tenant_id', 'ledger_direction', 'balance_effect']);
            $table->index(['original_invoice_id']);
            $table->index(['reversal_of_invoice_id']);
            $table->index(['invoice_date', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
