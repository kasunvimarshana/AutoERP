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
            $table->foreignId('reconciliation_id');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('bank_account_id');
            $table->date('statement_date');
            $table->string('reference', 150)->nullable();
            $table->string('description')->nullable();
            $table->decimal('debit', 20, 6)->default('0.000000');
            $table->decimal('credit', 20, 6)->default('0.000000');
            $table->foreignId('matched_ledger_entry_id')
                ->nullable();
            $table->string('status', 30)->default('unmatched');
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'bank_account_id'], 'finance_bank_stmt_scope_account_idx');
            $table->index(['reconciliation_id', 'status'], 'finance_bank_stmt_recon_status_idx');
            $table->index('matched_ledger_entry_id', 'finance_bank_stmt_ledger_idx');

            $table->unique(['id', 'tenant_id'], 'finance_bank_statement_lines_id_tenant_uk');
            $table->foreign(['reconciliation_id', 'tenant_id'], 'finance_bank_statement_lines_reconciliation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_bank_reconciliations')
                ->cascadeOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_bank_statement_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['bank_account_id', 'tenant_id'], 'finance_bank_statement_lines_bank_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
            $table->foreign(['matched_ledger_entry_id', 'tenant_id'], 'finance_bank_statement_lines_matched_ledger_entr_16443ecc_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_ledger_entries')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_statement_lines');
    }
};
