<?php

declare(strict_types=1);

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
        Schema::create('bills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('vendor_id');
            $table->ulid('purchase_order_id')->nullable();
            $table->ulid('goods_receipt_id')->nullable();
            $table->string('bill_code', 50)->unique();
            $table->string('vendor_invoice_number', 100)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('bill_date');
            $table->date('due_date');
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('shipping_cost', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6)->default(0);
            $table->decimal('paid_amount', 20, 6)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'vendor_id']);
            $table->index(['tenant_id', 'purchase_order_id']);
            $table->index(['tenant_id', 'goods_receipt_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'bill_date']);
            $table->index(['tenant_id', 'due_date']);
            $table->index(['tenant_id', 'bill_code']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'vendor_id', 'status']);
            $table->index(['tenant_id', 'organization_id', 'status']);
            $table->index(['tenant_id', 'status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
