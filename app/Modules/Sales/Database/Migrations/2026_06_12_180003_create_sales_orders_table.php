<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('sales_order_number');
            $table->date('sales_order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('sales_quotations')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('line_discount_total', 20, 6)->default('0.000000');
            $table->decimal('line_tax_total', 20, 6)->default('0.000000');
            $table->decimal('line_charge_total', 20, 6)->default('0.000000');
            $table->decimal('header_increase_total', 20, 6)->default('0.000000');
            $table->decimal('header_decrease_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->decimal('allocated_total', 20, 6)->default('0.000000');
            $table->decimal('delivered_total', 20, 6)->default('0.000000');
            $table->decimal('invoiced_total', 20, 6)->default('0.000000');
            $table->decimal('returned_total', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sales_order_number'], 'sales_orders_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_orders_scope_idx');
            $table->index(['customer_id', 'status'], 'sales_orders_customer_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
