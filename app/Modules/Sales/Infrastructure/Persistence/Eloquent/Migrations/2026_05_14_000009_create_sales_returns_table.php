<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('reference')->nullable();
            $table->foreignId('customer_id')->constrained('customers', 'id')->cascadeOnDelete();
            $table->foreignId('original_sales_order_id')->nullable()->constrained('sales_orders', 'id')->cascadeOnDelete();
            $table->foreignId('original_gdn_id')->nullable()->constrained('gdn_headers', 'id')->cascadeOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices', 'id')->cascadeOnDelete();
            $table->string('return_number');
            $table->string('status')->default('draft')->comment('draft, approved, closed, cancelled');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->date('return_date');
            $table->string('return_reason')->nullable();

            // ── Line‑derived totals – strictly SUM over lines ──
            $table->decimal('subtotal', 20, 4)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_tax_total', 20, 4)->default(0)->comment('SUM(line.tax_amount)');
            $table->decimal('line_discount_total', 20, 4)->default(0)->comment('SUM(line.discount_amount)');
             $table->decimal('line_restocking_total', 20, 4)->default(0)->comment('SUM(line.restocking_fee)');

            // ── Header‑level adjustments (applied on top of the order) ──
            $table->string('header_discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('header_discount_value', 20, 4)->nullable();
            $table->decimal('header_discount_amount', 20, 4)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 4)->default(0);

            // ── Final totals (combine line + header) ──
            $table->decimal('discount_total', 20, 4)->default(0)->comment('line_discount_total + header_discount_amount');
            $table->decimal('tax_total', 20, 4)->default(0)->comment('line_tax_total + header_tax_amount');
            $table->decimal('debit_note_total', 20, 4)->default(0)->comment('SUM of debit notes');
            $table->decimal('credit_note_total', 20, 4)->default(0)->comment('SUM of credit notes');
            $table->decimal('grand_total', 20, 4)->default(0)->comment('subtotal - discount_total + tax_total + credit_note_total - debit_note_total');

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'return_number'], 'sales_returns_return_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
