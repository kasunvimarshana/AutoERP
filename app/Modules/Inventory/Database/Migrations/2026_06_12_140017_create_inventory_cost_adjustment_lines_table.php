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
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'inventory_cost_adjustment_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('inventory_cost_adjustment_id');
            $table->foreignId('valuation_layer_id');
            $table->decimal('adjustment_amount', 20, 6);
            $table->decimal('remaining_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost_before', 20, 6)->default('0.000000');
            $table->decimal('unit_cost_after', 20, 6)->default('0.000000');
            $table->decimal('remaining_value_before', 20, 6)->default('0.000000');
            $table->decimal('remaining_value_after', 20, 6)->default('0.000000');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('inventory_cost_adjustment_id', 'inventory_cost_adj_lines_header_ix');
            $table->index('valuation_layer_id', 'inventory_cost_adj_lines_layer_ix');

            $table->unique(['id', 'tenant_id'], 'inventory_cost_adjustment_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_cost_adjustment_lines_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['inventory_cost_adjustment_id', 'tenant_id'], 'inventory_cost_adj_lines_header_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_cost_adjustments')
                ->restrictOnDelete();
            $table->foreign(['valuation_layer_id', 'tenant_id'], 'inventory_cost_adjustment_lines_valuation_layer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_valuation_layers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_adjustment_lines');
    }
};
