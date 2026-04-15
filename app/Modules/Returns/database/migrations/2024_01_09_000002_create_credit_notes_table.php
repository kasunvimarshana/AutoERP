<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('cn_number', 50)->unique();
            $table->enum('direction', ['issued_to_customer', 'received_from_supplier']);
            $table->unsignedBigInteger('party_id');
            $table->unsignedBigInteger('return_order_id')->nullable();
            $table->date('issue_date');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('amount', 18, 4);
            $table->decimal('remaining_amount', 18, 4);
            $table->enum('status', ['open', 'partial', 'applied', 'cancelled'])->default('open');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods')->cascadeOnDelete();
            $table->foreign('party_id')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('return_order_id')->references('id')->on('return_orders')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['tenant_id', 'party_id', 'status']);
            $table->index('cn_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};