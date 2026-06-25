<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('goods_receipt_note_id');
            $table->foreignId('purchase_order_line_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('ordered_uom_id')->nullable();
            $table->foreignId('base_uom_id')->nullable();
            $table->decimal('uom_conversion_factor', 20, 6)->default('1.000000');
            $table->foreignId('uom_id')->nullable();
            $table->decimal('ordered_quantity', 20, 6)->default('0.000000');
            $table->decimal('received_quantity', 20, 6);
            $table->decimal('base_received_quantity', 20, 6)->default('0.000000');
            $table->decimal('accepted_quantity', 20, 6);
            $table->decimal('base_accepted_quantity', 20, 6)->default('0.000000');
            $table->decimal('rejected_quantity', 20, 6)->default('0.000000');
            $table->decimal('invoiced_quantity', 20, 6)->default('0.000000');
            $table->decimal('returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('line_subtotal', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->foreignId('inventory_movement_id')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index('goods_receipt_note_id', 'goods_receipt_note_lines_grn_idx');
            $table->index('purchase_order_line_id', 'goods_receipt_note_lines_po_line_idx');
            $table->index('item_id', 'goods_receipt_note_lines_item_idx');
            $table->index('inventory_movement_id', 'goods_receipt_note_lines_movement_idx');
            $table->index('status', 'goods_receipt_note_lines_status_idx');
            $table->index('tax_group_id', 'goods_receipt_note_lines_tax_group_idx');
            $table->index(['goods_receipt_note_id', 'purchase_order_line_id'], 'goods_receipt_note_lines_grn_po_line_idx');
            $table->index(['goods_receipt_note_id', 'status'], 'goods_receipt_note_lines_grn_status_idx');
            $table->index(['goods_receipt_note_id', 'accepted_quantity', 'invoiced_quantity', 'returned_quantity'], 'goods_receipt_note_lines_balance_idx');

            $table->unique(['id', 'tenant_id'], 'goods_receipt_note_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'goods_receipt_note_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['goods_receipt_note_id', 'tenant_id'], 'goods_receipt_note_lines_goods_receipt_note_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('goods_receipt_notes')
                ->cascadeOnDelete();
            $table->foreign(['purchase_order_line_id', 'tenant_id'], 'goods_receipt_note_lines_purchase_order_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('purchase_order_lines')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'goods_receipt_note_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'goods_receipt_note_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['ordered_uom_id', 'tenant_id'], 'goods_receipt_note_lines_ordered_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['base_uom_id', 'tenant_id'], 'goods_receipt_note_lines_base_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'goods_receipt_note_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'goods_receipt_note_lines_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();
            $table->foreign(['inventory_movement_id', 'tenant_id'], 'goods_receipt_note_lines_inventory_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_lines');
    }
};
