<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations');
            $table->string('valuation_method')->nullable();
            $table->date('layer_date');
            $table->decimal('quantity_in', 20, 4);
            $table->decimal('quantity_remaining', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            // $table->decimal('total_cost', 20, 4)->storedAs('quantity_remaining * unit_cost')->comment('quantity_remaining * unit_cost');
            $table->nullableMorphs('reference');
            $table->boolean('is_closed')->default(false);

            $table->timestamps();

            $table->index(['tenant_id', 'item_id', 'layer_date'], 'inventory_cost_layers_item_layer_date_idx');
            $table->index(['tenant_id', 'item_id', 'is_closed'], 'inventory_cost_layers_item_is_closed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
