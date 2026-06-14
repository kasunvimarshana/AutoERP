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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->restrictOnDelete();
            $table->string('serial_number', 160);
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->restrictOnDelete();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_serial_numbers');
    }
};
