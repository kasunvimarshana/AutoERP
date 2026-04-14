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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->cascadeOnDelete();
            $table->string('payment_number', 50)->unique();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->date('payment_date');
            $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'card', 'credit_note', 'other']);
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('amount', 18, 4);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'bounced', 'cancelled'])->default('pending');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};