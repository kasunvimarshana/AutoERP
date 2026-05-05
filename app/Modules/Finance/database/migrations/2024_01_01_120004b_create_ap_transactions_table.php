<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'ap_transactions_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('supplier_id');
            $table->foreignId('account_id')->constrained(null, 'id', 'ap_transactions_account_id_fk')->cascadeOnDelete();
            $table->enum('transaction_type', ['bill', 'payment', 'debit_note', 'adjustment']);
            $table->nullableMorphs('reference');
            $table->decimal('amount', 20, 6);
            $table->decimal('balance_after', 20, 6);
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'ap_transactions_currency_id_fk');
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'supplier_id'], 'ap_transactions_tenant_supplier_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_transactions');
    }
};
