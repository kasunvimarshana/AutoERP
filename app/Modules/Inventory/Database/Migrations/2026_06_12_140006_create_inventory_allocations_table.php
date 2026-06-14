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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('allocation_number', 80);
            $table->date('allocation_date');
            $table->string('allocation_method', 30)->default('manual');
            $table->foreignId('reservation_id')->nullable()->constrained('inventory_reservations')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('base_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('entered_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->restrictOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->restrictOnDelete();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocations');
    }
};
