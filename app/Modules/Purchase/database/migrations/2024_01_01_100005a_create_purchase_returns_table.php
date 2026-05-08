<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('supplier_id');
            $table->foreignId('original_grn_id')->nullable()->constrained('grn_headers', 'id', 'purchase_returns_original_grn_id_fk')->nullOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('purchase_invoices', 'id', 'purchase_returns_original_invoice_id_fk')->nullOnDelete();
            $table->string('return_number');
            $table->string('status')->default('draft')->comment('draft, approved, shipped, closed, cancelled');
            $table->date('return_date');
            $table->string('return_reason')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'purchase_returns_currency_id_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->default(1);

            // ── Line‑derived totals – strictly SUM over lines ──
            $table->decimal('subtotal', 20, 6)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_restocking_total', 20, 6)->default(0)->comment('SUM(line.restocking_fee)');

            // ── Header‑level adjustments (applied on top of the order) ──
            $table->enum('header_discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('header_discount_value', 10, 6)->nullable();
            $table->decimal('header_discount_amount', 20, 6)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 6)->default(0);

            // ── Final totals (combine line + header) ──
            $table->decimal('discount_total', 20, 6)->default(0)->comment('header_discount_amount');
            $table->decimal('tax_total', 20, 6)->default(0)->comment('header_tax_amount');
            $table->decimal('surcharge_total', 20, 6)->default(0)->comment('SUM of surcharge notes');
            $table->decimal('credit_total', 20, 6)->default(0)->comment('SUM of credit notes');
            $table->decimal('grand_total', 20, 6)->default(0)->comment('subtotal - discount_total + tax_total + surcharge_total - credit_total - line_restocking_total');

            $table->string('debit_note_number')->nullable();
            $table->foreignId('journal_entry_id')->nullable();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by');

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullable()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'return_number'], 'purchase_returns_tenant_return_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
