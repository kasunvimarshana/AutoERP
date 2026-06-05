<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('invoice_line_id')->nullable()->constrained('invoice_lines')->nullOnDelete();
            $table->string('settlement_type', 50);
            // payment, advance_payment, refund, write_off, credit_application
            $table->unsignedBigInteger('settlement_id')->nullable();
            $table->string('effect', 20);
            // reduce_balance, increase_balance
            $table->decimal('amount', 20, 4);
            $table->decimal('base_amount', 20, 4)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->string('status', 30)->default('active');
            $table->date('settlement_date');
            $table->string('source_module', 100)->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status', 'settlement_date']);
            $table->index(['settlement_type', 'settlement_id']);
            $table->index(['source_module', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_settlements');
    }
};
