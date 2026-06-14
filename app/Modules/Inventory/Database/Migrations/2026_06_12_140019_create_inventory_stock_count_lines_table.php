<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_count_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('inventory_stock_count_id')->constrained('inventory_stock_counts')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('base_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('entered_uom_id')->nullable()->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->restrictOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->restrictOnDelete();
            $table->decimal('entered_system_quantity', 20, 6)->nullable();
            $table->decimal('entered_counted_quantity', 20, 6);
            $table->decimal('entered_unit_cost', 20, 6)->nullable();
            $table->decimal('conversion_factor', 20, 6)->default('1.000000');
            $table->decimal('system_quantity', 20, 6)->default('0.000000');
            $table->decimal('counted_quantity', 20, 6)->default('0.000000');
            $table->decimal('variance_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->foreignId('inventory_adjustment_line_id')->nullable()->constrained('inventory_adjustment_lines')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('inventory_stock_count_id', 'inventory_stock_count_lines_count_idx');
            $table->index('item_id', 'inventory_stock_count_lines_item_idx');
            $table->index('batch_id', 'inventory_stock_count_lines_batch_idx');
            $table->index('serial_number_id', 'inventory_stock_count_lines_serial_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_count_lines');
    }
};
