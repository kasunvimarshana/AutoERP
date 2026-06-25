<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_allocation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sales_allocation_id');
            $table->foreignId('sales_order_line_id');
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('uom_id')->nullable();
            $table->decimal('requested_quantity', 20, 6);
            $table->decimal('allocated_quantity', 20, 6)->default('0.000000');
            $table->decimal('released_quantity', 20, 6)->default('0.000000');
            $table->decimal('issued_quantity', 20, 6)->default('0.000000');
            $table->foreignId('inventory_allocation_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['sales_allocation_id', 'line_number'], 'sales_allocation_lines_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_allocation_lines_scope_idx');
            $table->index(['sales_order_line_id', 'status'], 'sales_allocation_lines_source_status_idx');
            $table->index('inventory_allocation_id', 'sales_allocation_lines_inventory_idx');

            $table->unique(['id', 'tenant_id'], 'sales_allocation_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_allocation_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['sales_allocation_id', 'tenant_id'], 'sales_allocation_lines_sales_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_allocations')
                ->cascadeOnDelete();
            $table->foreign(['sales_order_line_id', 'tenant_id'], 'sales_allocation_lines_sales_order_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_order_lines')
                ->cascadeOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'sales_allocation_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'sales_allocation_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'sales_allocation_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['inventory_allocation_id', 'tenant_id'], 'sales_allocation_lines_inventory_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_allocations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_allocation_lines');
    }
};
