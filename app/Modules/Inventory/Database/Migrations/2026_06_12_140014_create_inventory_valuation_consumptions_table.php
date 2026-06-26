<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_valuation_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'inventory_valuation_consumptions_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('issue_movement_id');
            $table->foreignId('valuation_layer_id');
            $table->decimal('quantity_consumed', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->foreignId('reversed_by_movement_id')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['issue_movement_id', 'valuation_layer_id'],
                'inventory_valuation_consumptions_issue_layer_uk',
            );
            $table->index('valuation_layer_id', 'inventory_valuation_consumptions_layer_ix');
            $table->index('reversed_by_movement_id', 'inventory_valuation_consumptions_reversal_ix');

            $table->unique(['id', 'tenant_id'], 'inventory_valuation_consumptions_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_valuation_consumptions_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['issue_movement_id', 'tenant_id'], 'inventory_valuation_consumptions_issue_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
            $table->foreign(['valuation_layer_id', 'tenant_id'], 'inventory_valuation_consumptions_valuation_layer_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_valuation_layers')
                ->restrictOnDelete();
            $table->foreign(['reversed_by_movement_id', 'tenant_id'], 'inventory_valuation_consumptions_reversed_by_movement_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_consumptions');
    }
};
