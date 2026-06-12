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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('journal_number', 100);
            $table->date('journal_date');
            $table->foreignId('fiscal_year_id')->nullable()->constrained('finance_fiscal_years', 'id')->nullOnDelete();
            $table->foreignId('fiscal_period_id')->nullable()->constrained('finance_fiscal_periods', 'id')->nullOnDelete();
            $table->foreignId('posting_profile_id')->nullable()->constrained('finance_posting_profiles', 'id')->nullOnDelete();
            $table->string('source_module', 100)->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_number', 150)->nullable();
            $table->date('source_date')->nullable();
            $table->enum('journal_type', ['general', 'invoice', 'payment', 'adjustment', 'reversal', 'opening'])->default('general');
            $table->enum('status', ['draft', 'posted', 'reversed', 'void', 'cancelled'])->default('draft');
            $table->text('description')->nullable();
            $table->decimal('total_debit', 20, 6)->default('0');
            $table->decimal('total_credit', 20, 6)->default('0');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('finance_journal_entries', 'id')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'journal_number'], 'finance_journals_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'finance_journals_tenant_org_idx');
            $table->index(['source_type', 'source_id'], 'finance_journals_source_idx');
            $table->index(
                ['tenant_id', 'source_module', 'source_type', 'source_id'],
                'finance_journals_tenant_source_idx',
            );
            $table->index('journal_date', 'finance_journals_date_idx');
            $table->index('status', 'finance_journals_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_entries');
    }
};
