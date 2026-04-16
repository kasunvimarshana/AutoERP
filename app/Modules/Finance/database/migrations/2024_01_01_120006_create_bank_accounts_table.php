<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('routing_number')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('current_balance', 15, 4)->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('feed_provider')->nullable(); // e.g., plaid, yodlee
            $table->text('feed_credentials_enc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 15, 4);
            $table->decimal('balance', 15, 4)->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('status', ['imported', 'categorized', 'reconciled', 'excluded'])->default('imported');
            // Bank transactions matched JE
            $table->foreignId('matched_journal_entry_id')->nullable();
            $table->foreignId('category_rule_id')->nullable();
            $table->foreign('matched_journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('category_rule_id')->references('id')->on('bank_category_rules')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bank_account_id', 'external_id'], 'uq_bank_trans_account_external');
        });

        Schema::create('bank_category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('priority')->default(0);
            $table->json('conditions'); // e.g., {"description_contains": "AMAZON"}
            $table->foreignId('account_id')->constrained();
            $table->string('description_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('opening_balance', 15, 4);
            $table->decimal('closing_balance', 15, 4);
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_category_rules');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};