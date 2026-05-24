<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('invoice_reference_id')->nullable()->constrained('invoice_references')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('item_type')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('description')->nullable();

            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures', 'id')->nullOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);

            // Discount stored as both configuration and calculated amount
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 4)
                ->storedAs('quantity * unit_price')
                ->comment('Gross = quantity * unit price');
            $table->decimal('line_total', 20, 4)
                ->storedAs('quantity * unit_price - discount_amount')
                ->comment('Net after discount before tax');

            // Tax
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('tax_amount', 20, 4)->default(0)
                ->comment('Calculated tax amount');

            // Stored line total including tax
            $table->decimal('line_total_with_tax', 20, 4)
                ->storedAs('quantity * unit_price - discount_amount + tax_amount')
                ->comment('total including tax');

            // Line posting account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete()->comment('Account used for posting this line');

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id'], 'invoice_lines_invoice_idx');
            $table->index(['tenant_id', 'item_type', 'item_id'], 'invoice_lines_item_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
