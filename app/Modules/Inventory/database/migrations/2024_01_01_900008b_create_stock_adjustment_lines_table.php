<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments', 'id', 'stock_adjustment_lines_stock_adjustment_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id', 'stock_adjustment_lines_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'stock_adjustment_lines_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id', 'stock_adjustment_lines_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'stock_adjustment_lines_serial_id_fk')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations', 'id', 'stock_adjustment_lines_location_id_fk')->cascadeOnDelete();
            $table->decimal('system_qty', 20, 6);
            $table->decimal('counted_qty', 20, 6);
            $table->decimal('variance_qty', 20, 6)->storedAs('counted_qty - system_qty');
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('variance_value', 20, 6)->storedAs('variance_qty * unit_cost');
            $table->foreignId('adjustment_movement_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_lines');
    }
};
