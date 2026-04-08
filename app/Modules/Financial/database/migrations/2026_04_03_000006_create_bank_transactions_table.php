<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('bank_account_id');
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            // deposit, withdrawal, fee, interest, transfer
            $table->string('type', 30);
            $table->decimal('amount', 15, 4);
            $table->decimal('balance', 15, 4)->default(0);
            $table->string('currency_code', 10)->default('USD');
            // pending, matched, reconciled, ignored
            $table->string('status', 30)->default('pending');
            $table->uuid('journal_entry_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['tenant_id', 'status']);

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
