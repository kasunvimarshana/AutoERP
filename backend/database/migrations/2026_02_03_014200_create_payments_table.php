<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->enum('payment_type', ['received', 'paid'])->default('received');
            
            // Polymorphic relationship to Customer or Vendor
            $table->string('entity_type')->nullable(); // Customer or Vendor
            $table->unsignedBigInteger('entity_id')->nullable();
            
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            
            // Amount details
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('base_amount', 15, 2); // Amount in base currency
            
            // Payment details
            $table->string('reference_number')->nullable();
            $table->string('transaction_id')->nullable();
            
            // Bank transfer details
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            
            // Cheque details
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            
            // Card details
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_type')->nullable();
            
            $table->text('notes')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->enum('reconciliation_status', ['unreconciled', 'partially_reconciled', 'reconciled'])->default('unreconciled');
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->json('metadata')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'payment_number']);
            $table->index(['tenant_id', 'payment_date']);
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'reconciliation_status']);
            $table->index(['tenant_id', 'payment_method_id']);
            $table->index('reference_number');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
