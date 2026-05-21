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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->foreignId('account_id')->constrained('accounts', 'id', 'ar_transactions_account_id_fk')->cascadeOnDelete();
            $table->string('transaction_type')->comment('BILL, PAYMENT, ADJUSTMENT, etc.');
            $table->nullableMorphs('reference');
            $table->decimal('debit_amount', 20, 4)->default(0);
            $table->decimal('credit_amount', 20, 4)->default(0);
            $table->decimal('balance_after', 20, 4)->default(0);
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'ar_transactions_currency_id_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->boolean('is_reconciled')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_transactions');
    }
};
