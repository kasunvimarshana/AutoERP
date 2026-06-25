<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('movement_number', 80);
            $table->date('movement_date');
            $table->string('movement_type', 40);
            $table->string('direction', 20);
            $table->foreignId('item_id');
            $table->foreignId('base_uom_id')->nullable();
            $table->foreignId('entered_uom_id')->nullable();
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->decimal('entered_quantity', 20, 6);
            $table->decimal('entered_unit_cost', 20, 6)->default('0.000000');
            $table->decimal('conversion_factor', 20, 6)->default('1.000000');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->decimal('total_cost', 20, 6)->default('0.000000');
            $table->decimal('balance_quantity_after', 20, 6)->default('0.000000');
            $table->decimal('balance_value_after', 20, 6)->default('0.000000');
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'movement_number'], 'inventory_movements_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_movements_tenant_org_idx');
            $table->index('movement_date', 'inventory_movements_date_idx');
            $table->index('item_id', 'inventory_movements_item_idx');
            $table->index('warehouse_id', 'inventory_movements_warehouse_idx');
            $table->index('warehouse_location_id', 'inventory_movements_location_idx');
            $table->index('batch_id', 'inventory_movements_batch_idx');
            $table->index('serial_number_id', 'inventory_movements_serial_idx');
            $table->index(['source_type', 'source_id'], 'inventory_movements_source_idx');
            $table->index(['source_line_type', 'source_line_id'], 'inventory_movements_source_line_idx');
            $table->index('status', 'inventory_movements_status_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_movements_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_movements_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_movements_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'inventory_movements_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['entered_uom_id', 'tenant_id'], 'inventory_movements_entered_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_movements_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'inventory_movements_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'inventory_movements_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_movements_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['serial_number_id', 'tenant_id'], 'inventory_movements_serial_number_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_serial_numbers')
                ->restrictOnDelete();
            $table->foreign(['reversal_of_id', 'tenant_id'], 'inventory_movements_reversal_of_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'inventory_movements_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['posted_by', 'tenant_id'], 'inventory_movements_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'inventory_movements_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
