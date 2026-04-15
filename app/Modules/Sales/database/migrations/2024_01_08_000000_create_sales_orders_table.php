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
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('so_number', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('status', ['draft', 'confirmed', 'picking', 'shipped', 'invoiced', 'cancelled'])->default('draft');
            $table->date('order_date');
            $table->date('requested_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->cascadeOnDelete();
            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->nullOnDelete();
            $table->foreign('billing_address_id')->references('id')->on('party_addresses')->nullOnDelete();
            $table->foreign('shipping_address_id')->references('id')->on('party_addresses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['tenant_id', 'customer_id', 'status']);
            $table->index('so_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};