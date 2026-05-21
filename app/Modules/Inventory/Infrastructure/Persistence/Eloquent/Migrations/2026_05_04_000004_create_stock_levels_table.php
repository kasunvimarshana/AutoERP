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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('unit_of_measures');
            // $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('quantity_on_hand', 20, 4)->default(0);
            $table->decimal('quantity_reserved', 20, 4)->default(0);
            // $table->decimal('quantity_available', 20, 4)->storedAs('quantity_on_hand - quantity_reserved')->comment('quantity_on_hand - quantity_reserved');
            // $table->decimal('unit_price', 20, 4)->nullable();
            $table->decimal('unit_cost', 20, 4)->nullable();
            // $table->decimal('value', 20, 4)->nullable();
            // $table->decimal('average_cost', 20, 4)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->string('condition')->default('good');

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'item_id', 'variant_id', 'location_id', 'batch_id', 'serial_id', 'condition'],
                'stock_levels_item_location_batch_serial_condition_uk'
            );
            $table->index(['tenant_id', 'item_id', 'location_id'], 'stock_levels_item_location_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
