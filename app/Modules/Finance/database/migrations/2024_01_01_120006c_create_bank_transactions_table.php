<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id', 'bank_transactions_tenant_id_fk')->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained(null, 'id', 'bank_transactions_bank_account_id_fk')->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 20, 6);
            $table->decimal('balance', 20, 6)->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('status', ['imported', 'categorized', 'reconciled', 'excluded'])->default('imported');
            $table->foreignId('matched_journal_entry_id')->nullable();
            $table->foreignId('category_rule_id')->nullable();
            $table->foreign('matched_journal_entry_id', 'bank_transactions_matched_journal_entry_id_fk')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('category_rule_id', 'bank_transactions_category_rule_id_fk')->references('id')->on('bank_category_rules')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bank_account_id', 'external_id'], 'bank_transactions_account_external_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
