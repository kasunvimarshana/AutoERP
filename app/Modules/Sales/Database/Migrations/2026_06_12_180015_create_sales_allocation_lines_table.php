<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_allocation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('sales_allocation_id')->constrained('sales_allocations')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->constrained('sales_order_lines')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('requested_quantity', 20, 6);
            $table->decimal('allocated_quantity', 20, 6)->default('0.000000');
            $table->decimal('released_quantity', 20, 6)->default('0.000000');
            $table->decimal('issued_quantity', 20, 6)->default('0.000000');
            $table->foreignId('inventory_allocation_id')->nullable()->constrained('inventory_allocations')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['sales_allocation_id', 'line_number'], 'sales_allocation_lines_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_allocation_lines_scope_idx');
            $table->index(['sales_order_line_id', 'status'], 'sales_allocation_lines_source_status_idx');
            $table->index('inventory_allocation_id', 'sales_allocation_lines_inventory_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_allocation_lines');
    }
};
