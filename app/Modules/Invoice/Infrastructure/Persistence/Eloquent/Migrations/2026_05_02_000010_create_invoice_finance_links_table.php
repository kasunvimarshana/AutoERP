<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_finance_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('ar_transaction_id')->nullable()->constrained('ar_transactions')->nullOnDelete();
            $table->foreignId('ap_transaction_id')->nullable()->constrained('ap_transactions')->nullOnDelete();
            $table->string('posting_role', 50)->default('primary');
            // primary, adjustment, settlement, reversal
            $table->string('status', 30)->default('posted');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'posting_role', 'status']);
            $table->index(['journal_entry_id']);
            $table->index(['ar_transaction_id']);
            $table->index(['ap_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_finance_links');
    }
};
