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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('invoice_reference_id')->constrained('invoice_references')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('item_type')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('description')->nullable();

            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures', 'id')->nullOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);

            // Discount – stored both as configuration and as absolute amount
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 4)
                  ->storedAs('quantity * unit_price')
                  ->comment('Gross = qty * unit price');
            $table->decimal('line_total', 20, 4)
                  ->storedAs('gross_amount - discount_amount')
                  ->comment('Net after discount before tax');

            // Tax
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('tax_amount', 20, 4)->default(0)
                  ->comment('Calculated tax amount');

            // Optional – if you want a stored line total including tax
            $table->decimal('line_total_with_tax', 20, 4)
                  ->storedAs('line_total + tax_amount')
                  ->comment('total including tax');

            // invoice lines account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete()->comment('account for posting');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
