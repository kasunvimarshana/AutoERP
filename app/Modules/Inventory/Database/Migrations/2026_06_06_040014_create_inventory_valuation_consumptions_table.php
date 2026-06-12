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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('issue_movement_id')->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('valuation_layer_id')->constrained('inventory_valuation_layers')->restrictOnDelete();
            $table->decimal('quantity_consumed', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->foreignId('reversed_by_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['issue_movement_id', 'valuation_layer_id'],
                'inventory_valuation_consumptions_issue_layer_uk',
            );
            $table->index('valuation_layer_id', 'inventory_valuation_consumptions_layer_idx');
            $table->index('reversed_by_movement_id', 'inventory_valuation_consumptions_reversal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_consumptions');
    }
};
