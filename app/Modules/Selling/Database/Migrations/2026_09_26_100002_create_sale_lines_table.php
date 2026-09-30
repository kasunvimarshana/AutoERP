<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sale_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sale_id');
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('uom_id');
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->decimal('base_quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('unit_cost_snapshot', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->string('description');
            $table->foreignId('inventory_movement_id')->nullable();
            $table->timestamps();

            $table->unique(['sale_id', 'line_number'], 'sale_lines_sale_line_uk');
            $table->index(['tenant_id', 'item_id'], 'sale_lines_tenant_item_ix');
            $table->unique(['id', 'tenant_id'], 'sale_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sale_lines_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['sale_id', 'tenant_id'], 'sale_lines_sale_tenant_fk')
                ->references(['id', 'tenant_id'])->on('sales')->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'sale_lines_item_tenant_fk')
                ->references(['id', 'tenant_id'])->on('items')->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'sale_lines_variant_tenant_fk')
                ->references(['id', 'tenant_id'])->on('item_variants')->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'sale_lines_uom_tenant_fk')
                ->references(['id', 'tenant_id'])->on('unit_of_measures')->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'sale_lines_batch_tenant_fk')
                ->references(['id', 'tenant_id'])->on('inventory_batches')->restrictOnDelete();
            $table->foreign(['serial_number_id', 'tenant_id'], 'sale_lines_serial_tenant_fk')
                ->references(['id', 'tenant_id'])->on('inventory_serial_numbers')->restrictOnDelete();
            $table->foreign(['inventory_movement_id', 'tenant_id'], 'sale_lines_movement_tenant_fk')
                ->references(['id', 'tenant_id'])->on('inventory_movements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
    }
};
