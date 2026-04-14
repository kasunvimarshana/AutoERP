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
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('status', ['pending', 'matched', 'categorized', 'excluded'])->default('pending');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedBigInteger('category_rule_id')->nullable();
            $table->enum('source', ['import', 'manual', 'api'])->default('import');
            $table->json('raw_data')->nullable();
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};