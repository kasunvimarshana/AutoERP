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
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sales_orders_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('sales_order_number');
            $table->date('sales_order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->foreignId('customer_id');
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('warehouse_location_id')->nullable();
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
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_orders_scope_ix');
            $table->index(['customer_id', 'status'], 'sales_orders_customer_status_ix');

            $table->unique(['id', 'tenant_id'], 'sales_orders_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_orders_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'sales_orders_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['quotation_id', 'tenant_id'], 'sales_orders_quotation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_quotations')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'sales_orders_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'sales_orders_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'sales_orders_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'sales_orders_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['cancelled_by', 'tenant_id'], 'sales_orders_cancelled_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['closed_by', 'tenant_id'], 'sales_orders_closed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
