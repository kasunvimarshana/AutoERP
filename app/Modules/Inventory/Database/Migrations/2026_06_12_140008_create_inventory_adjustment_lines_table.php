<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'inventory_adjustment_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('inventory_adjustment_id');
            $table->foreignId('item_id');
            $table->foreignId('base_uom_id')->nullable();
            $table->foreignId('entered_uom_id')->nullable();
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->decimal('entered_system_quantity', 20, 6)->default('0.000000');
            $table->decimal('entered_counted_quantity', 20, 6)->default('0.000000');
            $table->decimal('entered_adjustment_quantity', 20, 6);
            $table->decimal('entered_unit_cost', 20, 6)->default('0.000000');
            $table->decimal('conversion_factor', 20, 6)->default('1.000000');
            $table->decimal('system_quantity', 20, 6)->default('0.000000');
            $table->decimal('counted_quantity', 20, 6)->default('0.000000');
            $table->decimal('adjustment_quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->decimal('total_cost', 20, 6)->default('0.000000');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('inventory_adjustment_id', 'inventory_adjustment_lines_adjustment_ix');
            $table->index('item_id', 'inventory_adjustment_lines_item_ix');

            $table->unique(['id', 'tenant_id'], 'inventory_adjustment_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_adjustment_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['inventory_adjustment_id', 'tenant_id'], 'inventory_adjustment_lines_inventory_adjustment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_adjustments')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_adjustment_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'inventory_adjustment_lines_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['entered_uom_id', 'tenant_id'], 'inventory_adjustment_lines_entered_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_adjustment_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_adjustment_lines_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['serial_number_id', 'tenant_id'], 'inventory_adjustment_lines_serial_number_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_serial_numbers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_lines');
    }
};
