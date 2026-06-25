<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_delivery_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sales_delivery_id');
            $table->foreignId('sales_order_line_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->nullable();
            $table->decimal('ordered_quantity', 20, 6)->default('0.000000');
            $table->decimal('delivered_quantity', 20, 6);
            $table->decimal('invoiced_quantity', 20, 6)->default('0.000000');
            $table->decimal('returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('line_total', 20, 6);
            $table->foreignId('inventory_movement_id')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'sales_delivery_lines_scope_idx');
            $table->index('sales_order_line_id', 'sales_delivery_lines_order_line_idx');

            $table->unique(['id', 'tenant_id'], 'sales_delivery_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_delivery_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['sales_delivery_id', 'tenant_id'], 'sales_delivery_lines_sales_delivery_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_deliveries')
                ->cascadeOnDelete();
            $table->foreign(['sales_order_line_id', 'tenant_id'], 'sales_delivery_lines_sales_order_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_order_lines')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'sales_delivery_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'sales_delivery_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'sales_delivery_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['inventory_movement_id', 'tenant_id'], 'sales_delivery_lines_inventory_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_lines');
    }
};
