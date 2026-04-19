<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'sales_orders_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('customer_id');
            $table->foreignId('org_unit_id')->nullable();
            $table->foreignId('warehouse_id');
            $table->string('so_number');
            $table->enum('status', ['draft', 'confirmed', 'partial', 'shipped', 'invoiced', 'closed', 'cancelled'])->default('draft');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'sales_orders_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->date('order_date');
            $table->date('requested_delivery_date')->nullable();
            $table->foreignId('price_list_id')->nullable();
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('discount_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by');
            $table->foreignId('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'so_number'], 'sales_orders_tenant_so_number_uk');
            $table->index(['tenant_id', 'customer_id', 'status'], 'sales_orders_tenant_customer_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
