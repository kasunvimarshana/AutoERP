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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('so_number', 50);
            $table->foreignId('customer_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->enum('status', ['draft', 'confirmed', 'picking', 'shipped', 'invoiced', 'cancelled'])->default('draft');
            $table->date('order_date');
            $table->date('requested_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 8);
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->foreignId('billing_address_id')->nullable()->constrained('party_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('party_addresses')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'so_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
