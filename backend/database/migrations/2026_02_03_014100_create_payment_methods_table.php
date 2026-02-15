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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            $table->string('name');
            $table->string('code')->index();
            $table->enum('type', ['cash', 'bank_transfer', 'cheque', 'credit_card', 'debit_card', 'mobile_payment', 'online_payment', 'other'])->default('cash');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Configuration flags
            $table->boolean('requires_bank_details')->default(false);
            $table->boolean('requires_cheque_details')->default(false);
            $table->boolean('requires_card_details')->default(false);
            
            // Bank details (optional)
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('routing_number')->nullable();
            
            $table->integer('display_order')->default(0);
            $table->json('metadata')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
