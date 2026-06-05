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

            $table->string('invoice_type', 50);
            // sales, purchase, service, rental, mixed, manual, debit_note, credit_note

            $table->string('direction', 30);
            // inbound, outbound

            $table->string('party_type', 80)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_name')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->string('status', 50)->default('draft');
            // draft, approved, posted, partially_paid, paid, cancelled, reversed

            $table->decimal('gross_total', 20, 4)->default(0);
            $table->decimal('line_discount_total', 20, 4)->default(0);
            $table->decimal('header_discount_total', 20, 4)->default(0);
            $table->decimal('taxable_total', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('charge_total', 20, 4)->default(0);
            $table->decimal('rounding_adjustment', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->decimal('paid_total', 20, 4)->default(0);
            $table->decimal('balance_total', 20, 4)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->unsignedBigInteger('row_version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'organization_unit_id']);
            $table->index(['tenant_id', 'party_type', 'party_id']);
            $table->index(['tenant_id', 'invoice_type', 'status']);
            $table->index(['tenant_id', 'direction', 'status']);
            $table->index(['invoice_date', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
