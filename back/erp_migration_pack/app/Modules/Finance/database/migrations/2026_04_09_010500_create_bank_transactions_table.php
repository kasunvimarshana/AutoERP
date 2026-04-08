<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->string('transaction_reference');
            $table->date('transaction_date');
            $table->decimal('amount', 19, 4);
            $table->string('direction');
            $table->string('counterparty_name')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->default("imported");
            $table->json('raw_payload')->nullable();
            $table->foreignId('matched_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('matched_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unique(['tenant_id', 'bank_account_id', 'transaction_reference']);
            $table->index(['tenant_id', 'transaction_date']);
            $table->index(['tenant_id', 'bank_account_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
