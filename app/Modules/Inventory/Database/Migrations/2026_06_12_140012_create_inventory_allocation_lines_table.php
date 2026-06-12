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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('allocation_id')->constrained('inventory_allocations')->cascadeOnDelete();
            $table->foreignId('stock_balance_id')->constrained('inventory_stock_balances')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->nullOnDelete();
            $table->decimal('quantity_allocated', 20, 6);
            $table->decimal('quantity_issued', 20, 6)->default('0.000000');
            $table->decimal('quantity_reversed', 20, 6)->default('0.000000');
            $table->decimal('quantity_released', 20, 6)->default('0.000000');
            $table->decimal('quantity_remaining', 20, 6);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_allocation_lines_scope_idx');
            $table->index('allocation_id', 'inventory_allocation_lines_allocation_idx');
            $table->index('stock_balance_id', 'inventory_allocation_lines_balance_idx');
            $table->index('batch_id', 'inventory_allocation_lines_batch_idx');
            $table->index('serial_number_id', 'inventory_allocation_lines_serial_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocation_lines');
    }
};
