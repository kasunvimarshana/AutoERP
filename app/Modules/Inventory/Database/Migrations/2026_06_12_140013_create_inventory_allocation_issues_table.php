<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_allocation_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('allocation_id')->constrained('inventory_allocations')->restrictOnDelete();
            $table->foreignId('allocation_line_id')->constrained('inventory_allocation_lines')->restrictOnDelete();
            $table->foreignId('movement_id')->constrained('inventory_movements')->restrictOnDelete();
            $table->decimal('quantity_issued', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->foreignId('reversal_movement_id')->nullable()->constrained('inventory_movements')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique('movement_id', 'inventory_allocation_issues_movement_uk');
            $table->index('allocation_id', 'inventory_allocation_issues_allocation_idx');
            $table->index('allocation_line_id', 'inventory_allocation_issues_line_idx');
            $table->index('reversal_movement_id', 'inventory_allocation_issues_reversal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocation_issues');
    }
};
