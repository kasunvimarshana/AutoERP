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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('journal_entry_id')->constrained('finance_journal_entries', 'id')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts', 'id');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 6)->default('0');
            $table->decimal('credit', 20, 6)->default('0');
            $table->foreignId('dimension_id')->nullable()->constrained('finance_dimensions', 'id')->nullOnDelete();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->timestamps();

            $table->index('journal_entry_id', 'finance_journal_lines_entry_idx');
            $table->index('account_id', 'finance_journal_lines_account_idx');
            $table->index('dimension_id', 'finance_journal_lines_dimension_idx');
            $table->index(['source_line_type', 'source_line_id'], 'finance_journal_lines_source_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journal_lines');
    }
};
