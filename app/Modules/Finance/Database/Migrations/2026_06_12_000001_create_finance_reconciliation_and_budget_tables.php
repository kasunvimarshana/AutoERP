<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_bank_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained('finance_accounts', 'id')->restrictOnDelete();
            $table->string('statement_reference', 150);
            $table->date('statement_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('opening_balance', 20, 6)->default('0.000000');
            $table->decimal('closing_balance', 20, 6)->default('0.000000');
            $table->decimal('reconciled_balance', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'bank_account_id', 'statement_reference'],
                'finance_bank_recon_scope_statement_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id', 'bank_account_id'], 'finance_bank_recon_scope_account_idx');
            $table->index('status', 'finance_bank_recon_status_idx');
        });

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

        Schema::create('finance_budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('fiscal_year_id')->nullable()->constrained('finance_fiscal_years', 'id')->nullOnDelete();
            $table->unsignedSmallInteger('budget_year');
            $table->string('name');
            $table->string('status', 30)->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'budget_year', 'name'],
                'finance_budgets_scope_year_name_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id', 'budget_year'], 'finance_budgets_scope_year_idx');
        });

        Schema::create('finance_budget_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_id')->constrained('finance_budgets', 'id')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts', 'id')->restrictOnDelete();
            $table->foreignId('fiscal_period_id')->nullable()->constrained('finance_fiscal_periods', 'id')->nullOnDelete();
            $table->foreignId('dimension_id')->nullable()->constrained('finance_dimensions', 'id')->nullOnDelete();
            $table->unsignedTinyInteger('budget_month')->nullable();
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'account_id'], 'finance_budget_lines_scope_account_idx');
            $table->index(['budget_id', 'fiscal_period_id'], 'finance_budget_lines_budget_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budget_lines');
        Schema::dropIfExists('finance_budgets');
        Schema::dropIfExists('finance_bank_statement_lines');
        Schema::dropIfExists('finance_bank_reconciliations');
    }
};
