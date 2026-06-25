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
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('allocation_id');
            $table->foreignId('allocation_line_id');
            $table->foreignId('movement_id');
            $table->decimal('quantity_issued', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->foreignId('reversal_movement_id')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique('movement_id', 'inventory_allocation_issues_movement_uk');
            $table->index('allocation_id', 'inventory_allocation_issues_allocation_idx');
            $table->index('allocation_line_id', 'inventory_allocation_issues_line_idx');
            $table->index('reversal_movement_id', 'inventory_allocation_issues_reversal_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_allocation_issues_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_allocation_issues_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['allocation_id', 'tenant_id'], 'inventory_allocation_issues_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_allocations')
                ->restrictOnDelete();
            $table->foreign(['allocation_line_id', 'tenant_id'], 'inventory_allocation_issues_allocation_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_allocation_lines')
                ->restrictOnDelete();
            $table->foreign(['movement_id', 'tenant_id'], 'inventory_allocation_issues_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
            $table->foreign(['reversal_movement_id', 'tenant_id'], 'inventory_allocation_issues_reversal_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocation_issues');
    }
};
