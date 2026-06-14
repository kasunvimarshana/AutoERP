<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('base_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->restrictOnDelete();
            $table->char('dimension_key', 64);
            $table->decimal('quantity_on_hand', 20, 6)->default('0.000000');
            $table->decimal('quantity_reserved', 20, 6)->default('0.000000');
            $table->decimal('quantity_allocated', 20, 6)->default('0.000000');
            $table->decimal('quantity_available', 20, 6)->default('0.000000');
            $table->decimal('quantity_returned', 20, 6)->default('0.000000');
            $table->decimal('quantity_in_transit', 20, 6)->default('0.000000');
            $table->decimal('quantity_damaged', 20, 6)->default('0.000000');
            $table->decimal('quantity_quarantine', 20, 6)->default('0.000000');
            $table->decimal('quantity_expired', 20, 6)->default('0.000000');
            $table->decimal('quantity_scrapped', 20, 6)->default('0.000000');
            $table->decimal('average_cost', 20, 6)->default('0.000000');
            $table->decimal('total_value', 20, 6)->default('0.000000');
            $table->timestamps();

            $table->unique('dimension_key', 'inventory_stock_balances_dimension_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_stock_balances_tenant_org_idx');
            $table->index('item_id', 'inventory_stock_balances_item_idx');
            $table->index('warehouse_id', 'inventory_stock_balances_warehouse_idx');
            $table->index('warehouse_location_id', 'inventory_stock_balances_location_idx');
            $table->index('batch_id', 'inventory_stock_balances_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_balances');
    }
};
