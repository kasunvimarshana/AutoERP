<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('customer_id');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->enum('payment_method', ['cash', 'check', 'bank_transfer', 'credit_card', 'debit_card', 'online', 'other']);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('accounts');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'payment_date']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
