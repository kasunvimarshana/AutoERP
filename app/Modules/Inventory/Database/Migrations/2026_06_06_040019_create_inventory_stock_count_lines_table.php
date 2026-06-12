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
            $table->foreignId('inventory_stock_count_id')->constrained('inventory_stock_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->nullOnDelete();
            $table->decimal('system_quantity', 20, 6)->default('0.000000');
            $table->decimal('counted_quantity', 20, 6)->default('0.000000');
            $table->decimal('variance_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->foreignId('inventory_adjustment_line_id')->nullable()->constrained('inventory_adjustment_lines')->nullOnDelete();
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
