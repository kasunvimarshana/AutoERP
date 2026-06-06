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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->foreignId('movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_layers');
    }
};
