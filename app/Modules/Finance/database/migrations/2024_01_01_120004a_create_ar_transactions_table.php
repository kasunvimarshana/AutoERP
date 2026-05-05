<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'ar_transactions_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->foreignId('account_id')->constrained(null, 'id', 'ar_transactions_account_id_fk')->cascadeOnDelete();
            $table->enum('transaction_type', ['invoice', 'payment', 'credit_memo', 'adjustment']);
            $table->nullableMorphs('reference');
            $table->decimal('amount', 20, 6);
            $table->decimal('balance_after', 20, 6);
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'ar_transactions_currency_id_fk');
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id'], 'ar_transactions_tenant_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_transactions');
    }
};
