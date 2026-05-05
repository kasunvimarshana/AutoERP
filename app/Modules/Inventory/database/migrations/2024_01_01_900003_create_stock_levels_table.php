<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('product_id')->constrained(null, 'id', 'stock_levels_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'stock_levels_variant_id_fk')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations', 'id', 'stock_levels_location_id_fk')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained(null, 'id', 'stock_levels_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained(null, 'id', 'stock_levels_serial_id_fk')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'stock_levels_uom_id_fk');
            $table->decimal('quantity_on_hand', 20, 6)->default(0);
            $table->decimal('quantity_reserved', 20, 6)->default(0);
            $table->decimal('quantity_available', 20, 6)->storedAs('quantity_on_hand - quantity_reserved');
            $table->decimal('unit_cost', 20, 6)->nullable();
            // $table->decimal('purchase_price', 20, 6)->nullable();
            // $table->decimal('sales_price', 20, 6)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id', 'variant_id', 'location_id', 'batch_id', 'serial_id'], 'stock_levels_tenant_product_loc_batch_serial_uk');
            $table->index(['tenant_id', 'product_id'], 'stock_levels_tenant_product_idx');
            $table->index(['tenant_id', 'location_id', 'product_id'], 'stock_levels_tenant_location_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
