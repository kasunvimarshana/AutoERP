<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_state_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('stock_balance_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('warehouse_location_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
            $table->decimal('quantity', 20, 6);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_state_changes_scope_idx');
            $table->index(['source_type', 'source_id'], 'inventory_state_changes_source_idx');
            $table->index(['item_id', 'warehouse_id'], 'inventory_state_changes_item_wh_idx');
            $table->index(['from_state', 'to_state'], 'inventory_state_changes_states_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_stock_state_changes_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_stock_state_changes_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['stock_balance_id', 'tenant_id'], 'inventory_stock_state_changes_stock_balance_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_stock_balances')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_stock_state_changes_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_stock_state_changes_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'inventory_stock_state_changes_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'inventory_stock_state_changes_warehouse_location_29e48847_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_stock_state_changes_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['serial_number_id', 'tenant_id'], 'inventory_stock_state_changes_serial_number_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_serial_numbers')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'inventory_stock_state_changes_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_state_changes');
    }
};
