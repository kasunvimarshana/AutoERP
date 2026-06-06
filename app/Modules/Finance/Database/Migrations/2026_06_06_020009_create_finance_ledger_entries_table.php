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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('journal_entry_id')->constrained('finance_journal_entries', 'id')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->constrained('finance_journal_lines', 'id')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts', 'id');
            $table->date('entry_date');
            $table->decimal('debit', 20, 6)->default('0');
            $table->decimal('credit', 20, 6)->default('0');
            $table->decimal('balance_after', 20, 6)->default('0');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'finance_ledger_tenant_org_idx');
            $table->index('account_id', 'finance_ledger_account_idx');
            $table->index('entry_date', 'finance_ledger_date_idx');
            $table->index(['source_type', 'source_id'], 'finance_ledger_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ledger_entries');
    }
};
