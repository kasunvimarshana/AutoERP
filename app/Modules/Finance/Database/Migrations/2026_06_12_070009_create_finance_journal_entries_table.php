<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_journal_entries_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('journal_number', 100);
            $table->date('journal_date');
            $table->foreignId('fiscal_year_id')->nullable();
            $table->foreignId('fiscal_period_id')->nullable();
            $table->foreignId('posting_profile_id')->nullable();
            $table->string('source_module', 100)->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_number', 150)->nullable();
            $table->date('source_date')->nullable();
            $table->enum('journal_type', ['general', 'invoice', 'payment', 'contra', 'adjustment', 'reversal', 'opening'])->default('general');
            $table->enum('status', ['draft', 'posted', 'reversed', 'void', 'cancelled'])->default('draft');
            $table->text('description')->nullable();
            $table->decimal('total_debit', 20, 6)->default('0');
            $table->decimal('total_credit', 20, 6)->default('0');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', indexName: 'finance_journal_entries_currency_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'journal_number'], 'finance_journals_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'finance_journals_tenant_org_ix');
            $table->index(['source_type', 'source_id'], 'finance_journals_source_ix');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'finance_journals_tenant_source_ix', );
            $table->index('journal_date', 'finance_journals_date_ix');
            $table->index('status', 'finance_journals_status_ix');

            $table->unique(['id', 'tenant_id'], 'finance_journal_entries_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_journal_entries_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['fiscal_year_id', 'tenant_id'], 'finance_journal_entries_fiscal_year_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_fiscal_years')
                ->restrictOnDelete();
            $table->foreign(['fiscal_period_id', 'tenant_id'], 'finance_journal_entries_fiscal_period_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_fiscal_periods')
                ->restrictOnDelete();
            $table->foreign(['posting_profile_id', 'tenant_id'], 'finance_journal_entries_posting_profile_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_posting_profiles')
                ->restrictOnDelete();
            $table->foreign(['reversal_of_id', 'tenant_id'], 'finance_journal_entries_reversal_of_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_journal_entries')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'finance_journal_entries_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['posted_by', 'tenant_id'], 'finance_journal_entries_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'finance_journal_entries_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_entries');
    }
};
