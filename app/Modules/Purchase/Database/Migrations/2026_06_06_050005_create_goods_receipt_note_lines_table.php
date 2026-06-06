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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('goods_receipt_note_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('ordered_quantity', 20, 6)->default('0.000000');
            $table->decimal('received_quantity', 20, 6);
            $table->decimal('accepted_quantity', 20, 6);
            $table->decimal('rejected_quantity', 20, 6)->default('0.000000');
            $table->decimal('invoiced_quantity', 20, 6)->default('0.000000');
            $table->decimal('returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_lines');
    }
};
