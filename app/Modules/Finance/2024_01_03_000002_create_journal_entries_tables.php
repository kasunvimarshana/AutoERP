<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal Entries & Lines — the heart of double-entry accounting.
     *
     * Golden Rules enforced at application level:
     *   SUM(debit) = SUM(credit) per journal_entry
     *
     * period_id enforces accrual accounting period assignment.
     * source_type + source_id (polymorphic) links to originating document
     *   (invoice, payment, GRN, stock movement, etc.)
     *
     * entry_date  = business/transaction date
     * post_date   = accounting/posting date (may differ — accrual accounting)
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');              // REQUIRED — accrual period
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');                           // transaction date
            $table->date('post_date');                            // accounting date (accrual)
            $table->string('source_type', 100)->nullable();       // polymorphic: App\Models\CustomerInvoice
            $table->unsignedBigInteger('source_id')->nullable();  // polymorphic ID
            $table->string('reference', 100)->nullable();
            $table->text('description');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->unsignedBigInteger('reversed_by')->nullable(); // FK to self — reversing entry
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'period_id', 'status']);
            $table->index(['tenant_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 18, 4)->default(0);           // functional currency
            $table->decimal('credit', 18, 4)->default(0);
            $table->decimal('base_debit', 18, 4)->default(0);      // base/reporting currency
            $table->decimal('base_credit', 18, 4)->default(0);
            $table->unsignedBigInteger('party_id')->nullable();     // sub-ledger link (AP/AR)
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('line_number');

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts');
            $table->foreign('party_id')->references('id')->on('parties')->nullOnDelete();

            $table->index(['journal_entry_id']);
            $table->index(['account_id']);
            $table->index(['party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
    }
};
