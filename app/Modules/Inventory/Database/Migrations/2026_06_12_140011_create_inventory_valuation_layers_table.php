<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_valuation_layers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('base_uom_id')->nullable();
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->foreignId('movement_id')->nullable();
            $table->string('valuation_method', 40);
            $table->decimal('original_quantity', 20, 6);
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->decimal('remaining_value', 20, 6);
            $table->string('status', 30)->default('open');
            $table->timestamps();

            $table->index('item_id', 'inventory_valuation_layers_item_idx');
            $table->index('warehouse_id', 'inventory_valuation_layers_warehouse_idx');
            $table->index('batch_id', 'inventory_valuation_layers_batch_idx');
            $table->index(['source_type', 'source_id'], 'inventory_valuation_layers_source_idx');
            $table->index(['source_line_type', 'source_line_id'], 'inventory_valuation_layers_source_line_idx');
            $table->index('movement_id', 'inventory_valuation_layers_movement_idx');
            $table->index('status', 'inventory_valuation_layers_status_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_valuation_layers_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_valuation_layers_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_valuation_layers_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'inventory_valuation_layers_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_valuation_layers_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'inventory_valuation_layers_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'inventory_valuation_layers_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_valuation_layers_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['movement_id', 'tenant_id'], 'inventory_valuation_layers_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_layers');
    }
};
