<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('branch_id')->index();
            $table->uuid('customer_id')->index();
            $table->uuid('order_id')->nullable()->index();
            $table->string('invoice_number', 100)->unique();
            $table->date('invoice_date')->index();
            $table->date('due_date')->index();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            
            $table->index(['tenant_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['tenant_id', 'payment_status']);
            $table->index(['branch_id', 'payment_status']);
            $table->index(['tenant_id', 'invoice_date']);
            $table->index(['branch_id', 'invoice_date']);
            $table->index(['tenant_id', 'due_date']);
            $table->index(['due_date', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
