<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_layer_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('inventory_layer_id')->constrained('inventory_layers')->cascadeOnDelete();
            $table->foreignId('stock_movement_line_id')->constrained('stock_movement_lines')->cascadeOnDelete();
            $table->decimal('qty_consumed', 24, 8);
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('total_cost', 19, 4);
            $table->index(['tenant_id', 'inventory_layer_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_layer_consumptions');
    }
};
