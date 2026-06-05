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
            $table->string('invoice_type', 50);
            $table->string('direction', 30);

            $table->string('party_type', 80)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_name')->nullable();

            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->string('status', 50)->default('draft');

            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_total', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('charge_total', 20, 4)->default(0);
            $table->decimal('adjustment_total', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('balance_amount', 20, 4)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number'], 'invoices_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'invoices_tenant_org_idx');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'invoices_tenant_party_idx');
            $table->index(['tenant_id', 'invoice_type', 'status'], 'invoices_tenant_type_status_idx');
            $table->index(['tenant_id', 'direction', 'status'], 'invoices_tenant_direction_status_idx');
            $table->index(['invoice_date', 'due_date'], 'invoices_invoice_due_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
