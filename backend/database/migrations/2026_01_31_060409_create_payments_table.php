<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('branch_id')->index();
            $table->uuid('customer_id')->index();
            $table->uuid('invoice_id')->nullable()->index();
            $table->string('payment_number', 100)->unique();
            $table->date('payment_date')->index();
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'cheque', 'online', 'other'])->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending')->index();
            $table->string('reference_number')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            
            $table->index(['tenant_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['tenant_id', 'payment_method']);
            $table->index(['branch_id', 'payment_method']);
            $table->index(['tenant_id', 'payment_date']);
            $table->index(['branch_id', 'payment_date']);
            $table->index(['payment_date', 'status']);
            $table->index(['payment_method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
