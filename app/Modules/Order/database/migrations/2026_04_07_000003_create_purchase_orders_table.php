<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('order_number', 50);
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->uuid('supplier_id');
            $table->uuid('warehouse_id')->nullable();
            // draft, sent, partial_receipt, received, cancelled
            $table->string('status', 30)->default('draft');
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('shipping_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->string('payment_status', 30)->default('unpaid');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
