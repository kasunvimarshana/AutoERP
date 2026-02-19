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
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('customer_id');
            $table->ulid('quotation_id')->nullable();
            $table->string('order_code', 50)->unique();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('shipping_cost', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6)->default(0);
            $table->decimal('paid_amount', 20, 6)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'quotation_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'order_date']);
            $table->index(['tenant_id', 'order_code']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'customer_id', 'status']);
            $table->index(['tenant_id', 'organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
