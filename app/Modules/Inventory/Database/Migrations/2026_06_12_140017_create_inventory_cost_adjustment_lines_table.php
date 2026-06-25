<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cost_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('inventory_cost_adjustment_id');
            $table->foreign('inventory_cost_adjustment_id', 'inventory_cost_adj_lines_header_fk')
                ->references('id')
                ->on('inventory_cost_adjustments')
                ->restrictOnDelete();
            $table->foreignId('valuation_layer_id')->constrained('inventory_valuation_layers')->restrictOnDelete();
            $table->decimal('adjustment_amount', 20, 6);
            $table->decimal('remaining_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost_before', 20, 6)->default('0.000000');
            $table->decimal('unit_cost_after', 20, 6)->default('0.000000');
            $table->decimal('remaining_value_before', 20, 6)->default('0.000000');
            $table->decimal('remaining_value_after', 20, 6)->default('0.000000');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('inventory_cost_adjustment_id', 'inventory_cost_adj_lines_header_idx');
            $table->index('valuation_layer_id', 'inventory_cost_adj_lines_layer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_adjustment_lines');
    }
};
