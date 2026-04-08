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
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('credit_card_id')->nullable()->constrained('credit_cards')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('payment_number');
            $table->string('payment_direction');
            $table->string('payment_method');
            $table->string('status')->default("draft");
            $table->date('payment_date');
            $table->decimal('amount', 19, 4);
            $table->string('reference_number')->nullable();
            $table->text('memo')->nullable();
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'payment_number']);
            $table->index(['tenant_id', 'party_id']);
            $table->index(['tenant_id', 'payment_date']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
