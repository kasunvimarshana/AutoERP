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
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            
            // Polymorphic relationship to Invoice, PurchaseOrder, SalesOrder, etc.
            $table->string('allocatable_type');
            $table->unsignedBigInteger('allocatable_id');
            
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('base_amount', 15, 2);
            
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'payment_id']);
            $table->index(['tenant_id', 'allocatable_type', 'allocatable_id']);
            $table->index(['payment_id', 'allocatable_type', 'allocatable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
