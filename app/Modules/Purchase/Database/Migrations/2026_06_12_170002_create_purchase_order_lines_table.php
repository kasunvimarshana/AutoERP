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
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('purchase_order_id');
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('ordered_uom_id')->nullable();
            $table->foreignId('base_uom_id')->nullable();
            $table->decimal('uom_conversion_factor', 20, 6)->default('1.000000');
            $table->foreignId('uom_id')->nullable();
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
            $table->foreignId('tax_group_id')->nullable();
            $table->string('charge_calculation_type')->default('fixed');
            $table->decimal('charge_rate', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index('purchase_order_id', 'purchase_order_lines_order_idx');
            $table->index('item_id', 'purchase_order_lines_item_idx');
            $table->index('status', 'purchase_order_lines_status_idx');
            $table->index('tax_group_id', 'purchase_order_lines_tax_group_idx');
            $table->unique(['purchase_order_id', 'line_number'], 'purchase_order_lines_order_line_number_uk');
            $table->index(['purchase_order_id', 'status'], 'purchase_order_lines_order_status_idx');
            $table->index(['purchase_order_id', 'received_quantity', 'invoiced_quantity', 'returned_quantity'], 'purchase_order_lines_balance_idx');

            $table->unique(['id', 'tenant_id'], 'purchase_order_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'purchase_order_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['purchase_order_id', 'tenant_id'], 'purchase_order_lines_purchase_order_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('purchase_orders')
                ->cascadeOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'purchase_order_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'purchase_order_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['ordered_uom_id', 'tenant_id'], 'purchase_order_lines_ordered_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'purchase_order_lines_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'purchase_order_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'purchase_order_lines_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
