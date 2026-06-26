<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sales_deliveries_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('delivery_number');
            $table->date('delivery_date');
            $table->foreignId('sales_order_id')->nullable();
            $table->foreignId('customer_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'delivery_number'], 'sales_deliveries_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_deliveries_scope_ix');
            $table->index(['customer_id', 'status'], 'sales_deliveries_customer_status_ix');

            $table->unique(['id', 'tenant_id'], 'sales_deliveries_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_deliveries_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['sales_order_id', 'tenant_id'], 'sales_deliveries_sales_order_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_orders')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'sales_deliveries_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'sales_deliveries_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'sales_deliveries_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();

            $table->foreign(['posted_by', 'tenant_id'], 'sales_deliveries_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'sales_deliveries_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_deliveries');
    }
};
