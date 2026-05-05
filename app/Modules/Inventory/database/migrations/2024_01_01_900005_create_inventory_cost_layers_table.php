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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('product_id')->constrained(null, 'id', 'inventory_cost_layers_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'inventory_cost_layers_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained(null, 'id', 'inventory_cost_layers_batch_id_fk')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations', 'id', 'inventory_cost_layers_location_id_fk')->cascadeOnDelete();
            $table->enum('valuation_method', ['fifo', 'lifo', 'fefo', 'weighted_average', 'standard', 'specific'])->default('fifo');
            $table->date('layer_date'); // Date of receipt
            $table->decimal('quantity_in', 20, 6);
            $table->decimal('quantity_remaining', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6)->storedAs('quantity_remaining * unit_cost');
            $table->nullableMorphs('reference'); // link to stock_movement, GRN, etc.
            $table->boolean('is_closed')->default(false); // Layer exhausted
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'layer_date'], 'inventory_cost_layers_tenant_product_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
