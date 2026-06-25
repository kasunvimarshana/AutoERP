<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('allocation_number');
            $table->date('allocation_date');
            $table->foreignId('sales_order_id');
            $table->foreignId('customer_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'allocation_number'], 'sales_allocations_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_allocations_scope_idx');
            $table->index(['sales_order_id', 'status'], 'sales_allocations_order_status_idx');

            $table->unique(['id', 'tenant_id'], 'sales_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_allocations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['sales_order_id', 'tenant_id'], 'sales_allocations_sales_order_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_orders')
                ->cascadeOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'sales_allocations_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'sales_allocations_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'sales_allocations_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'sales_allocations_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_allocations');
    }
};
