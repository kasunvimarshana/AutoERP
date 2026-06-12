<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('ordered_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('base_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('uom_conversion_factor', 20, 6)->default('1.000000');
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('ordered_quantity', 20, 6);
            $table->decimal('base_quantity', 20, 6)->default('0.000000');
            $table->decimal('received_quantity', 20, 6)->default('0.000000');
            $table->decimal('invoiced_quantity', 20, 6)->default('0.000000');
            $table->decimal('returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('cancelled_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('remaining_receivable_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_invoiceable_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_returnable_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_price', 20, 6);
            $table->decimal('line_subtotal', 20, 6)->default('0.000000');
            $table->string('discount_calculation_type')->default('fixed');
            $table->decimal('discount_rate', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->string('tax_calculation_type')->default('fixed');
            $table->decimal('tax_rate', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->string('charge_calculation_type')->default('fixed');
            $table->decimal('charge_rate', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index('purchase_order_id', 'purchase_order_lines_order_idx');
            $table->index('item_id', 'purchase_order_lines_item_idx');
            $table->index('status', 'purchase_order_lines_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
