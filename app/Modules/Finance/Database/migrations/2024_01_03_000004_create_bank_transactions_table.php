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
            $table->unsignedBigInteger('bank_account_id');
            $table->date('transaction_date');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('status', ['pending', 'matched', 'categorized', 'excluded'])->default('pending');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('category_rule_id')->nullable();
            $table->enum('source', ['import', 'manual', 'api'])->default('import');
            $table->json('raw_data')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->index(['bank_account_id', 'transaction_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};