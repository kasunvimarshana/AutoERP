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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('external_id')->nullable()->comment('bank-provided transaction ID');
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->string('description');
            $table->decimal('amount', 20, 4);
            $table->string('type')->default('debit')->comment('debit, credit');
            $table->decimal('balance', 20, 4)->nullable()->comment('running balance from bank');
            $table->string('status')->default('imported')->comment('imported, categorized, matched, reconciled, excluded');
            $table->foreignId('matched_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('category_rule_id')->nullable()->constrained('bank_category_rules')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'bank_account_id', 'external_id'], 'bank_transactions_account_external_id_uk');
            $table->index(['tenant_id', 'bank_account_id', 'transaction_date'], 'bank_transactions_date_idx');
            $table->index(['tenant_id', 'status'], 'bank_transactions_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
