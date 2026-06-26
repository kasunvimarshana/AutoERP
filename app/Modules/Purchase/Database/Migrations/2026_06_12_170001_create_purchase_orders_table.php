<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'purchase_orders_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('supplier_type')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('warehouse_location_id')->nullable();
            $table->string('purchase_order_number');
            $table->date('purchase_order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('discount_total', 20, 6)->default('0.000000');
            $table->decimal('tax_total', 20, 6)->default('0.000000');
            $table->decimal('charge_total', 20, 6)->default('0.000000');
            $table->decimal('adjustment_total', 20, 6)->default('0.000000');
            $table->decimal('header_increase_total', 20, 6)->default('0.000000');
            $table->decimal('header_decrease_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'purchase_order_number'], 'purchase_orders_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'purchase_orders_tenant_org_ix');
            $table->index(['supplier_type', 'supplier_id'], 'purchase_orders_supplier_ix');
            $table->index('status', 'purchase_orders_status_ix');
            $table->index('purchase_order_date', 'purchase_orders_date_ix');

            $table->unique(['id', 'tenant_id'], 'purchase_orders_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'purchase_orders_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'purchase_orders_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'purchase_orders_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'purchase_orders_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['submitted_by', 'tenant_id'], 'purchase_orders_submitted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'purchase_orders_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['closed_by', 'tenant_id'], 'purchase_orders_closed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
