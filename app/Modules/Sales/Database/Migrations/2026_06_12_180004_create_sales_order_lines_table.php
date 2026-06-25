<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('quotation_line_id')->nullable()->constrained('sales_quotation_lines')->nullOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('ordered_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('base_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('uom_conversion_factor', 20, 6)->default('1.000000');
            $table->decimal('ordered_quantity', 20, 6);
            $table->decimal('base_quantity', 20, 6);
            $table->decimal('allocated_quantity', 20, 6)->default('0.000000');
            $table->decimal('delivered_quantity', 20, 6)->default('0.000000');
            $table->decimal('invoiced_quantity', 20, 6)->default('0.000000');
            $table->decimal('returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('cancelled_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_allocatable_quantity', 20, 6);
            $table->decimal('remaining_deliverable_quantity', 20, 6);
            $table->decimal('remaining_invoiceable_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_returnable_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_price', 20, 6);
            $table->decimal('line_subtotal', 20, 6);
            $table->string('discount_calculation_type')->nullable();
            $table->decimal('discount_rate', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->string('tax_calculation_type')->nullable();
            $table->decimal('tax_rate', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->string('charge_calculation_type')->nullable();
            $table->decimal('charge_rate', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->foreignId('inventory_allocation_id')->nullable()->constrained('inventory_allocations')->nullOnDelete();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->unique(['sales_order_id', 'line_number'], 'sales_order_lines_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_order_lines_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
