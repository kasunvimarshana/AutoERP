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
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('inventory_stock_count_id');
            $table->foreignId('item_id');
            $table->foreignId('base_uom_id')->nullable();
            $table->foreignId('entered_uom_id')->nullable();
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->decimal('entered_system_quantity', 20, 6)->nullable();
            $table->decimal('entered_counted_quantity', 20, 6);
            $table->decimal('entered_unit_cost', 20, 6)->nullable();
            $table->decimal('conversion_factor', 20, 6)->default('1.000000');
            $table->decimal('system_quantity', 20, 6)->default('0.000000');
            $table->decimal('counted_quantity', 20, 6)->default('0.000000');
            $table->decimal('variance_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->foreignId('inventory_adjustment_line_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('inventory_stock_count_id', 'inventory_stock_count_lines_count_idx');
            $table->index('item_id', 'inventory_stock_count_lines_item_idx');
            $table->index('batch_id', 'inventory_stock_count_lines_batch_idx');
            $table->index('serial_number_id', 'inventory_stock_count_lines_serial_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_stock_count_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_stock_count_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['inventory_stock_count_id', 'tenant_id'], 'inventory_stock_count_lines_inventory_stock_coun_2ce87e46_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_stock_counts')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_stock_count_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'inventory_stock_count_lines_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['entered_uom_id', 'tenant_id'], 'inventory_stock_count_lines_entered_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_stock_count_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_stock_count_lines_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['serial_number_id', 'tenant_id'], 'inventory_stock_count_lines_serial_number_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_serial_numbers')
                ->restrictOnDelete();
            $table->foreign(['inventory_adjustment_line_id', 'tenant_id'], 'inventory_stock_count_lines_inventory_adjustment_d9c6216b_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_adjustment_lines')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_count_lines');
    }
};
