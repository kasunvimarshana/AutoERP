<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('order_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->date('order_date');
            $table->date('required_date')->nullable();
            $table->date('promised_date')->nullable();
            $table->string('status', 50)->default('draft'); // draft, confirmed, processing, shipped, delivered, cancelled
            $table->string('payment_status', 50)->default('pending'); // pending, partial, paid, refunded
            $table->string('fulfillment_status', 50)->default('unfulfilled'); // unfulfilled, partial, fulfilled
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->string('tax_type', 50)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('adjustment_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'order_date']);
            $table->index(['customer_id', 'order_date']);
            $table->index('order_number');
            $table->index('order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
