<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_taxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->foreignId('invoice_line_id')->constrained('invoice_lines', 'id')->cascadeOnDelete();

            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates', 'id')->nullOnDelete();
            $table->string('tax_name');
            $table->decimal('tax_rate', 20, 4)->default(0);
            $table->decimal('taxable_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id'], 'invoice_line_taxes_invoice_idx');
            $table->index(['tenant_id', 'invoice_line_id'], 'invoice_line_taxes_line_idx');
            $table->index(['tenant_id', 'tax_rate_id'], 'invoice_line_taxes_rate_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_taxes');
    }
};
