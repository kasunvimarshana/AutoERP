<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_fiscal_years', function (Blueprint $table): void {
            $table->string('status', 30)->default('open')->change();
            $table->unique(
                ['tenant_id', 'organization_unit_id', 'start_date', 'end_date'],
                'finance_fiscal_years_scope_dates_uk',
            );
        });

        Schema::table('finance_fiscal_periods', function (Blueprint $table): void {
            $table->string('status', 30)->default('open')->change();
            $table->unique(
                ['fiscal_year_id', 'period_number'],
                'finance_fiscal_periods_year_number_uk',
            );
            $table->index(
                ['tenant_id', 'organization_unit_id', 'start_date', 'end_date'],
                'finance_fiscal_periods_scope_dates_idx',
            );
        });

        Schema::create('finance_posting_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'code'],
                'finance_posting_profiles_scope_code_uk',
            );
        });

        Schema::create('finance_posting_profile_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('posting_profile_id')
                ->constrained('finance_posting_profiles', 'id')
                ->cascadeOnDelete();
            $table->string('line_key', 100);
            $table->foreignId('account_id')->constrained('finance_accounts', 'id');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(
                ['posting_profile_id', 'line_key'],
                'finance_posting_profile_rules_profile_key_uk',
            );
        });

        Schema::table('finance_journal_entries', function (Blueprint $table): void {
            $table->foreignId('posting_profile_id')
                ->nullable()
                ->after('fiscal_period_id')
                ->constrained('finance_posting_profiles', 'id')
                ->nullOnDelete();
            $table->string('source_module', 100)->nullable()->after('posting_profile_id');
            $table->string('source_number', 150)->nullable()->after('source_id');
            $table->date('source_date')->nullable()->after('source_number');
            $table->text('reversal_reason')->nullable()->after('reversal_of_id');

            $table->index(
                ['tenant_id', 'source_module', 'source_type', 'source_id'],
                'finance_journals_tenant_source_idx',
            );
        });

        Schema::table('finance_journal_lines', function (Blueprint $table): void {
            $table->dropForeign(['journal_entry_id']);
            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('finance_journal_entries')
                ->restrictOnDelete();
        });

        Schema::table('finance_ledger_entries', function (Blueprint $table): void {
            $table->dropForeign(['journal_entry_id']);
            $table->dropForeign(['journal_line_id']);
            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('finance_journal_entries')
                ->restrictOnDelete();
            $table->foreign('journal_line_id')
                ->references('id')
                ->on('finance_journal_lines')
                ->restrictOnDelete();

            $table->foreignId('fiscal_year_id')
                ->nullable()
                ->after('account_id')
                ->constrained('finance_fiscal_years', 'id')
                ->restrictOnDelete();
            $table->foreignId('fiscal_period_id')
                ->nullable()
                ->after('fiscal_year_id')
                ->constrained('finance_fiscal_periods', 'id')
                ->restrictOnDelete();
            $table->foreignId('dimension_id')
                ->nullable()
                ->after('fiscal_period_id')
                ->constrained('finance_dimensions', 'id')
                ->restrictOnDelete();
            $table->string('source_module', 100)->nullable()->after('balance_after');
            $table->string('source_number', 150)->nullable()->after('source_id');
            $table->date('source_date')->nullable()->after('source_number');

            $table->index(
                ['tenant_id', 'organization_unit_id', 'fiscal_period_id'],
                'finance_ledger_scope_period_idx',
            );
            $table->index(
                ['tenant_id', 'source_module', 'source_type', 'source_id'],
                'finance_ledger_tenant_source_trace_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_ledger_entries', function (Blueprint $table): void {
            $table->dropIndex('finance_ledger_scope_period_idx');
            $table->dropIndex('finance_ledger_tenant_source_trace_idx');
            $table->dropForeign(['fiscal_year_id']);
            $table->dropForeign(['fiscal_period_id']);
            $table->dropForeign(['dimension_id']);
            $table->dropColumn([
                'fiscal_year_id',
                'fiscal_period_id',
                'dimension_id',
                'source_module',
                'source_number',
                'source_date',
            ]);

            $table->dropForeign(['journal_entry_id']);
            $table->dropForeign(['journal_line_id']);
            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('finance_journal_entries')
                ->cascadeOnDelete();
            $table->foreign('journal_line_id')
                ->references('id')
                ->on('finance_journal_lines')
                ->cascadeOnDelete();
        });

        Schema::table('finance_journal_lines', function (Blueprint $table): void {
            $table->dropForeign(['journal_entry_id']);
            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('finance_journal_entries')
                ->cascadeOnDelete();
        });

        Schema::table('finance_journal_entries', function (Blueprint $table): void {
            $table->dropIndex('finance_journals_tenant_source_idx');
            $table->dropForeign(['posting_profile_id']);
            $table->dropColumn([
                'posting_profile_id',
                'source_module',
                'source_number',
                'source_date',
                'reversal_reason',
            ]);
        });

        Schema::dropIfExists('finance_posting_profile_rules');
        Schema::dropIfExists('finance_posting_profiles');

        Schema::table('finance_fiscal_periods', function (Blueprint $table): void {
            $table->dropUnique('finance_fiscal_periods_year_number_uk');
            $table->dropIndex('finance_fiscal_periods_scope_dates_idx');
        });

        Schema::table('finance_fiscal_years', function (Blueprint $table): void {
            $table->dropUnique('finance_fiscal_years_scope_dates_uk');
        });
    }
};
