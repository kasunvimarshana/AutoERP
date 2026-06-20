<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('goods_receipt_note_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('ordered_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('base_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('uom_conversion_factor', 20, 6)->default('1.000000');
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
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
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
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
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement("ALTER TABLE goods_receipt_note_lines ADD CONSTRAINT goods_receipt_note_lines_status_chk CHECK (status IN ('open','posted','reversed'))");
            DB::statement('ALTER TABLE goods_receipt_note_lines ADD CONSTRAINT goods_receipt_note_lines_quantities_chk CHECK (received_quantity >= 0 AND accepted_quantity >= 0 AND rejected_quantity >= 0 AND invoiced_quantity >= 0 AND returned_quantity >= 0 AND accepted_quantity + rejected_quantity = received_quantity AND invoiced_quantity <= accepted_quantity AND returned_quantity <= accepted_quantity)');
            DB::statement('ALTER TABLE goods_receipt_note_lines ADD CONSTRAINT goods_receipt_note_lines_money_chk CHECK (unit_price >= 0 AND line_subtotal >= 0 AND discount_amount >= 0 AND tax_amount >= 0 AND charge_amount >= 0 AND line_total >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_lines');
    }
};
