<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('journal_entry_id');
            $table->foreignId('journal_line_id');
            $table->foreignId('account_id');
            $table->foreignId('fiscal_year_id')->nullable();
            $table->foreignId('fiscal_period_id')->nullable();
            $table->foreignId('dimension_id')->nullable();
            $table->date('entry_date');
            $table->decimal('debit', 20, 6)->default('0');
            $table->decimal('credit', 20, 6)->default('0');
            $table->decimal('balance_after', 20, 6)->default('0');
            $table->string('source_module', 100)->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_number', 150)->nullable();
            $table->date('source_date')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'finance_ledger_tenant_org_idx');
            $table->index('account_id', 'finance_ledger_account_idx');
            $table->index('entry_date', 'finance_ledger_date_idx');
            $table->index(['source_type', 'source_id'], 'finance_ledger_source_idx');
            $table->index(
                ['tenant_id', 'organization_unit_id', 'fiscal_period_id'],
                'finance_ledger_scope_period_idx',
            );
            $table->index(
                ['tenant_id', 'source_module', 'source_type', 'source_id'],
                'finance_ledger_tenant_source_trace_idx',
            );

            $table->unique(['id', 'tenant_id'], 'finance_ledger_entries_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_ledger_entries_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['journal_entry_id', 'tenant_id'], 'finance_ledger_entries_journal_entry_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_journal_entries')
                ->restrictOnDelete();
            $table->foreign(['journal_line_id', 'tenant_id'], 'finance_ledger_entries_journal_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_journal_lines')
                ->restrictOnDelete();
            $table->foreign(['account_id', 'tenant_id'], 'finance_ledger_entries_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
            $table->foreign(['fiscal_year_id', 'tenant_id'], 'finance_ledger_entries_fiscal_year_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_fiscal_years')
                ->restrictOnDelete();
            $table->foreign(['fiscal_period_id', 'tenant_id'], 'finance_ledger_entries_fiscal_period_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_fiscal_periods')
                ->restrictOnDelete();
            $table->foreign(['dimension_id', 'tenant_id'], 'finance_ledger_entries_dimension_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_dimensions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ledger_entries');
    }
};
