<?php

declare(strict_types=1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->constrained()->cascadeOnDelete();
            $table->string('entry_number')->nullable();
            $table->enum('entry_type', ['manual', 'auto', 'system'])->default('manual');
            $table->nullableMorphs('reference'); // purchase invoice, sales invoice, payment, etc.
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->date('posting_date')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by');
            $table->foreignId('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'entry_number'], 'uq_journal_entries_tenant_number');
            $table->index(['tenant_id', 'fiscal_period_id', 'status'], 'idx_je_tenant_period_status');
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit_amount', 15, 4)->default(0);
            $table->decimal('credit_amount', 15, 4)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('base_debit_amount', 15, 4)->default(0);
            $table->decimal('base_credit_amount', 15, 4)->default(0);
            $table->foreignId('cost_center_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'journal_entry_id'], 'idx_jel_account_entry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};