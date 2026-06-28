<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_journal_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('journal_entry_id');
            $table->foreignId('account_id');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 6)->default('0');
            $table->decimal('credit', 20, 6)->default('0');
            $table->foreignId('dimension_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->timestamps();

            $table->index('journal_entry_id', 'finance_journal_lines_entry_ix');
            $table->index('account_id', 'finance_journal_lines_account_ix');
            $table->index('dimension_id', 'finance_journal_lines_dimension_ix');
            $table->index(['source_line_type', 'source_line_id'], 'finance_journal_lines_source_line_ix');

            $table->unique(['id', 'tenant_id'], 'finance_journal_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_journal_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['journal_entry_id', 'tenant_id'], 'finance_journal_lines_journal_entry_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_journal_entries')
                ->restrictOnDelete();
            $table->foreign(['account_id', 'tenant_id'], 'finance_journal_lines_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
            $table->foreign(['dimension_id', 'tenant_id'], 'finance_journal_lines_dimension_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_dimensions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_lines');
    }
};
