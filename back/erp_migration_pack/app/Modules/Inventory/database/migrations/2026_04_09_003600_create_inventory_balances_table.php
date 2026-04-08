<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->cascadeOnDelete();
            $table->decimal('qty_on_hand', 24, 8)->default(0);
            $table->decimal('qty_reserved', 24, 8)->default(0);
            $table->decimal('qty_available', 24, 8)->default(0);
            $table->decimal('qty_damaged', 24, 8)->default(0);
            $table->decimal('qty_in_transit', 24, 8)->default(0);
            $table->unique(['warehouse_id', 'location_id', 'product_variant_id', 'lot_id', 'serial_id', 'unit_of_measure_id']);
            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'location_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
