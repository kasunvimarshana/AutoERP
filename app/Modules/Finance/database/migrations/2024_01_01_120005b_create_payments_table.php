<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('payment_number');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('party_type', ['customer', 'supplier']);
            $table->unsignedBigInteger('party_id');
            $table->foreignId('payment_method_id')->constrained(null, 'id', 'payments_payment_method_id_fk');
            $table->foreignId('account_id')->constrained(null, 'id', 'payments_account_id_fk');
            $table->decimal('amount', 20, 6);
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'payments_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('base_amount', 20, 6);
            $table->date('payment_date');
            $table->enum('status', ['draft', 'posted', 'reconciled', 'voided'])->default('draft');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreign('journal_entry_id', 'payments_journal_entry_id_fk')->references('id')->on('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'payment_number'], 'payments_tenant_number_uk');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'payments_tenant_party_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
