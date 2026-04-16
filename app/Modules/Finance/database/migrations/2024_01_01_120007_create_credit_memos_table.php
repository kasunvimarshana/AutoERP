<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id'); // customer or supplier
            $table->string('party_type'); // customer, supplier
            $table->foreignId('return_order_id')->nullable(); // polymorphic to purchase_return or sales_return
            $table->string('return_order_type')->nullable();
            $table->string('credit_memo_number');
            $table->decimal('amount', 15, 4);
            $table->enum('status', ['draft', 'issued', 'applied', 'voided'])->default('draft');
            $table->date('issued_date');
            $table->foreignId('applied_to_invoice_id')->nullable(); // reference to purchase_invoice or sales_invoice
            $table->string('applied_to_invoice_type')->nullable();
            $table->text('notes')->nullable();
            // Credit memos JE
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'credit_memo_number'], 'uq_credit_memos_tenant_number');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'idx_credit_memos_tenant_party');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_memos');
    }
};