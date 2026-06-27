<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_journal_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('journal_entry_id');
            $table->foreignId('account_id');
            $table->foreignId('account_role_id')->nullable();
            $table->string('account_code_snapshot', 100);
            $table->string('account_name_snapshot');
            $table->string('account_role_code_snapshot', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 6)->default('0.000000');
            $table->decimal('credit', 20, 6)->default('0.000000');
            $table->foreignId('dimension_id')->nullable();
            $table->string('source_line_type', 150)->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'finance_journal_lines_id_tenant_uk');
            $table->index('journal_entry_id', 'finance_journal_lines_entry_ix');
            $table->index('account_id', 'finance_journal_lines_account_ix');
            $table->index('account_role_id', 'finance_journal_lines_role_ix');
            $table->index('dimension_id', 'finance_journal_lines_dimension_ix');
            $table->index(['source_line_type', 'source_line_id'], 'finance_journal_lines_source_line_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_journal_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['journal_entry_id', 'tenant_id'], 'finance_journal_lines_journal_entry_id_tenant_fk')
                ->references(['id', 'tenant_id'])->on('finance_journal_entries')->restrictOnDelete();
            $table->foreign(['account_id', 'tenant_id'], 'finance_journal_lines_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])->on('finance_accounts')->restrictOnDelete();
            $table->foreign(['account_role_id', 'tenant_id'], 'finance_journal_lines_account_role_id_tenant_fk')
                ->references(['id', 'tenant_id'])->on('finance_account_roles')->restrictOnDelete();
            $table->foreign(['dimension_id', 'tenant_id'], 'finance_journal_lines_dimension_id_tenant_fk')
                ->references(['id', 'tenant_id'])->on('finance_dimensions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_lines');
    }
};
