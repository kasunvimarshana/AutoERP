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
            $table->foreignId('tenant_id')->constrained(null, 'id', 'purchase_returns_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id');
            $table->foreignId('original_grn_id')->nullable()->constrained('grn_headers', 'id', 'purchase_returns_original_grn_id_fk')->nullOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('purchase_invoices', 'id', 'purchase_returns_original_invoice_id_fk')->nullOnDelete();
            $table->string('return_number');
            $table->enum('status', ['draft', 'approved', 'shipped', 'closed', 'cancelled'])->default('draft');
            $table->date('return_date');
            $table->string('return_reason')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'purchase_returns_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->string('debit_note_number')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'return_number'], 'purchase_returns_tenant_return_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
