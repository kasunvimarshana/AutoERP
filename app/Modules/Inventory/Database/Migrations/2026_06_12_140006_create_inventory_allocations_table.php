<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('allocation_number', 80);
            $table->date('allocation_date');
            $table->string('allocation_method', 30)->default('manual');
            $table->foreignId('reservation_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('base_uom_id')->nullable();
            $table->foreignId('entered_uom_id')->nullable();
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->decimal('entered_quantity', 20, 6);
            $table->decimal('conversion_factor', 20, 6)->default('1.000000');
            $table->decimal('quantity_allocated', 20, 6);
            $table->decimal('quantity_issued', 20, 6)->default('0.000000');
            $table->decimal('quantity_reversed', 20, 6)->default('0.000000');
            $table->decimal('quantity_released', 20, 6)->default('0.000000');
            $table->decimal('quantity_remaining', 20, 6);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'allocation_number'], 'inventory_allocations_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_allocations_tenant_org_idx');
            $table->index('reservation_id', 'inventory_allocations_reservation_idx');
            $table->index('item_id', 'inventory_allocations_item_idx');
            $table->index('warehouse_id', 'inventory_allocations_warehouse_idx');
            $table->index('batch_id', 'inventory_allocations_batch_idx');
            $table->index('serial_number_id', 'inventory_allocations_serial_idx');
            $table->index(['source_type', 'source_id'], 'inventory_allocations_source_idx');
            $table->index(['source_line_type', 'source_line_id'], 'inventory_allocations_source_line_idx');
            $table->index('status', 'inventory_allocations_status_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_allocations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['reservation_id', 'tenant_id'], 'inventory_allocations_reservation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_reservations')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_allocations_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'inventory_allocations_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['entered_uom_id', 'tenant_id'], 'inventory_allocations_entered_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_allocations_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'inventory_allocations_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'inventory_allocations_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_allocations_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['serial_number_id', 'tenant_id'], 'inventory_allocations_serial_number_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_serial_numbers')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'inventory_allocations_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['issued_by', 'tenant_id'], 'inventory_allocations_issued_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocations');
    }
};
