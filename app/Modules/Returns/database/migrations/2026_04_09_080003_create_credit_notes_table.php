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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('cn_number', 50);
            $table->enum('direction', ['issued_to_customer', 'received_from_supplier']);
            $table->foreignId('party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('return_order_id')->nullable()->constrained('return_orders')->nullOnDelete();
            $table->date('issue_date');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('amount', 18, 4);
            $table->decimal('remaining_amount', 18, 4);
            $table->enum('status', ['open', 'partial', 'applied', 'cancelled'])->default('open');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'cn_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
