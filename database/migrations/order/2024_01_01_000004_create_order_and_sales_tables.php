<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sales Orders
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->index();
            $table->foreignUuid('customer_id')->constrained('users'); // Simplified, should be from CRM
            $table->enum('status', ['DRAFT', 'PENDING', 'PAID', 'SHIPPED', 'COMPLETED', 'CANCELLED'])->default('DRAFT');
            $table->decimal('sub_total', 20, 4);
            $table->decimal('tax_total', 20, 4);
            $table->decimal('discount_total', 20, 4);
            $table->decimal('grand_total', 20, 4);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1.0);
            $table->foreignUuid('user_id')->constrained(); // Who created the order
            $table->timestamp('order_date')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'order_number']);
        });

        // 2. Order Items
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained();
            $table->string('product_sku');
            $table->string('product_name');
            $table->decimal('quantity', 20, 4);
            $table->foreignUuid('uom_id')->constrained('uoms');
            $table->decimal('unit_price', 20, 4);
            $table->decimal('tax_amount', 20, 4);
            $table->decimal('discount_amount', 20, 4);
            $table->decimal('total_price', 20, 4);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 3. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sales_order_id')->constrained();
            $table->string('transaction_reference')->unique();
            $table->enum('method', ['CASH', 'CARD', 'BANK_TRANSFER', 'WALLET'])->default('CASH');
            $table->decimal('amount', 20, 4);
            $table->string('currency_code', 3);
            $table->enum('status', ['PENDING', 'COMPLETED', 'FAILED', 'REFUNDED'])->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
