<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('journal_entry_line_id')->nullable()->constrained('journal_entry_lines')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->date('posting_date');
            $table->string('entry_type')->comment('DEBIT, CREDIT');
            $table->decimal('amount', 20, 4)->default(0);
            $table->decimal('running_balance', 20, 4)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'account_id', 'posting_date'], 'ledger_entries_account_date_idx');
            $table->index(['tenant_id', 'journal_entry_id'], 'ledger_entries_journal_entry_idx');
            $table->index(['tenant_id', 'journal_entry_line_id'], 'ledger_entries_journal_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
