<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_bank_statement_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reconciliation_id')
                ->constrained('finance_bank_reconciliations', 'id')
                ->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained('finance_accounts', 'id')->restrictOnDelete();
            $table->date('statement_date');
            $table->string('reference', 150)->nullable();
            $table->string('description')->nullable();
            $table->decimal('debit', 20, 6)->default('0.000000');
            $table->decimal('credit', 20, 6)->default('0.000000');
            $table->foreignId('matched_ledger_entry_id')
                ->nullable()
                ->constrained('finance_ledger_entries', 'id')
                ->restrictOnDelete();
            $table->string('status', 30)->default('unmatched');
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'bank_account_id'], 'finance_bank_stmt_scope_account_idx');
            $table->index(['reconciliation_id', 'status'], 'finance_bank_stmt_recon_status_idx');
            $table->index('matched_ledger_entry_id', 'finance_bank_stmt_ledger_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_statement_lines');
    }
};
