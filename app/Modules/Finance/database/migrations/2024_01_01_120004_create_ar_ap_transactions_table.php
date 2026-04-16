<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->enum('transaction_type', ['invoice', 'payment', 'credit_memo', 'adjustment']);
            $table->nullableMorphs('reference');
            $table->decimal('amount', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
        });

        Schema::create('ap_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->enum('transaction_type', ['bill', 'payment', 'debit_note', 'adjustment']);
            $table->nullableMorphs('reference');
            $table->decimal('amount', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_transactions');
        Schema::dropIfExists('ar_transactions');
    }
};