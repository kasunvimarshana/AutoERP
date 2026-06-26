<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_allocation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'inventory_allocation_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('allocation_id');
            $table->foreignId('stock_balance_id');
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->decimal('quantity_allocated', 20, 6);
            $table->decimal('quantity_issued', 20, 6)->default('0.000000');
            $table->decimal('quantity_reversed', 20, 6)->default('0.000000');
            $table->decimal('quantity_released', 20, 6)->default('0.000000');
            $table->decimal('quantity_remaining', 20, 6);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_allocation_lines_scope_ix');
            $table->index('allocation_id', 'inventory_allocation_lines_allocation_ix');
            $table->index('stock_balance_id', 'inventory_allocation_lines_balance_ix');
            $table->index('batch_id', 'inventory_allocation_lines_batch_ix');
            $table->index('serial_number_id', 'inventory_allocation_lines_serial_ix');

            $table->unique(['id', 'tenant_id'], 'inventory_allocation_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_allocation_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['allocation_id', 'tenant_id'], 'inventory_allocation_lines_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_allocations')
                ->restrictOnDelete();
            $table->foreign(['stock_balance_id', 'tenant_id'], 'inventory_allocation_lines_stock_balance_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_stock_balances')
                ->restrictOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'inventory_allocation_lines_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['serial_number_id', 'tenant_id'], 'inventory_allocation_lines_serial_number_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_serial_numbers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocation_lines');
    }
};
