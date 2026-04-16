<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['cash', 'bank_transfer', 'card', 'cheque', 'other'])->default('bank_transfer');
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('party_type', ['customer', 'supplier']);
            $table->unsignedBigInteger('party_id');
            $table->foreignId('payment_method_id')->constrained();
            $table->foreignId('account_id')->constrained(); // bank/cash account
            $table->decimal('amount', 15, 4);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('base_amount', 15, 4);
            $table->date('payment_date');
            $table->enum('status', ['draft', 'posted', 'reconciled', 'voided'])->default('draft');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            // Payments JE
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'payment_number'], 'uq_payments_tenant_number');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'idx_payments_tenant_party');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->morphs('invoice'); // purchase_invoice or sales_invoice
            $table->decimal('allocated_amount', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};