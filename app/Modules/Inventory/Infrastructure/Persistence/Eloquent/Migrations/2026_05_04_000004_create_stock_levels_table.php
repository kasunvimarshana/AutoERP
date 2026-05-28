<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Current stock snapshot. (inventory_balances)
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('base_uom_id')->constrained('unit_of_measures');
            $table->decimal('quantity_on_hand', 20, 4)->default(0);
            $table->decimal('quantity_reserved', 20, 4)->default(0);
            $table->decimal('quantity_blocked', 20, 4)->default(0);
            $table->decimal('quantity_damaged', 20, 4)->default(0);
            $table->decimal('quantity_in_transit', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->string('condition')->default('good');
            $table->decimal('minimum_quantity', 20, 4)->nullable();
            $table->decimal('maximum_quantity', 20, 4)->nullable();
            $table->decimal('reorder_quantity', 20, 4)->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'tenant_id',
                    'item_id',
                    'variant_id',
                    'warehouse_id',
                    'location_id',
                    'batch_id',
                    'serial_id',
                    'condition',
                ],
                'stock_levels_item_wh_loc_batch_serial_condition_uk'
            );
            $table->index(['tenant_id', 'item_id', 'warehouse_id', 'location_id'], 'stock_levels_item_location_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
