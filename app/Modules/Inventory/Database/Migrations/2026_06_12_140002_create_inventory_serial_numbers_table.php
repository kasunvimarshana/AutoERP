<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_serial_numbers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->string('serial_number', 160);
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('warehouse_location_id')->nullable();
            $table->string('status', 30)->default('available');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'serial_number'], 'inventory_serials_tenant_serial_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_serials_tenant_org_idx');
            $table->index('item_id', 'inventory_serials_item_idx');
            $table->index('serial_number', 'inventory_serials_serial_idx');
            $table->index('batch_id', 'inventory_serials_batch_idx');
            $table->index('warehouse_id', 'inventory_serials_warehouse_idx');
            $table->index('warehouse_location_id', 'inventory_serials_location_idx');
            $table->index('status', 'inventory_serials_status_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_serial_numbers_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_serial_numbers_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_serial_numbers_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_serial_numbers_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_serial_numbers_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'inventory_serial_numbers_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'inventory_serial_numbers_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_serial_numbers');
    }
};
