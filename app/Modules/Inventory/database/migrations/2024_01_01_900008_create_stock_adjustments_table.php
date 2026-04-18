<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'stock_adjustments_tenant_id_fk')->cascadeOnDelete();
            $table->string('reference_number');
            $table->foreignId('warehouse_id')->constrained(null, 'id', 'stock_adjustments_warehouse_id_fk')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations', 'id', 'stock_adjustments_location_id_fk')->nullOnDelete();
            $table->enum('type', ['cycle_count', 'physical_inventory', 'write_off'])->default('cycle_count');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('counted_by')->nullable()->constrained('users', 'id', 'stock_adjustments_counted_by_fk')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id', 'stock_adjustments_approved_by_fk')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_number'], 'stock_adjustments_tenant_ref_uk');
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained(null, 'id', 'stock_adjustment_lines_stock_adjustment_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(null, 'id', 'stock_adjustment_lines_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'stock_adjustment_lines_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained(null, 'id', 'stock_adjustment_lines_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained(null, 'id', 'stock_adjustment_lines_serial_id_fk')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations', 'id', 'stock_adjustment_lines_location_id_fk')->cascadeOnDelete();
            $table->decimal('system_qty', 15, 4);
            $table->decimal('counted_qty', 15, 4);
            $table->decimal('variance_qty', 15, 4)->storedAs('counted_qty - system_qty');
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('variance_value', 15, 4)->storedAs('variance_qty * unit_cost');
            $table->foreignId('adjustment_movement_id')->nullable(); // reference to stock_movements
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
    }
};
