<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procurement Module
 * Purchase Orders → Goods Receipts (GRN) → Quality Check → Put-away
 * Supports: multi-currency, landed costs, partial receipts, backorders,
 * multi-step receiving, drop-shipping, consignment, blanket POs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Purchase Order Headers ───────────────────────────────────────────
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable(); // Default receiving warehouse

            $table->string('po_number', 100);
            $table->string('supplier_reference', 150)->nullable();  // Supplier's order ref
            $table->unsignedBigInteger('supplier_id');

            $table->string('po_type', 30)->default('standard');
            // standard | blanket | drop_ship | consignment | service | inter_company | emergency

            $table->string('status', 30)->default('draft');
            // draft | confirmed | partial | received | billed | done | cancelled

            // Dates
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('confirmed_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->date('expiry_date')->nullable();  // For blanket POs

            // Pricing
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 20, 8)->default(1.00000000);
            $table->unsignedBigInteger('price_list_id')->nullable();

            // Totals (computed)
            $table->decimal('subtotal', 19, 6)->default(0);
            $table->decimal('discount_amount', 19, 6)->default(0);
            $table->decimal('tax_amount', 19, 6)->default(0);
            $table->decimal('shipping_amount', 19, 6)->default(0);
            $table->decimal('landed_cost_amount', 19, 6)->default(0);
            $table->decimal('total_amount', 19, 6)->default(0);
            $table->decimal('amount_billed', 19, 6)->default(0);
            $table->decimal('amount_paid', 19, 6)->default(0);

            // Payment
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->date('payment_due_date')->nullable();

            // Shipping / Delivery
            $table->unsignedBigInteger('delivery_location_id')->nullable(); // FK warehouse_locations
            $table->string('incoterms', 20)->nullable();  // EXW, FOB, CIF, DDP, etc.
            $table->string('shipping_method', 100)->nullable();
            $table->string('carrier_name', 100)->nullable();

            // Approval workflow
            $table->string('approval_status', 30)->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Multi-step receiving flags
            $table->boolean('require_quality_check')->default(false);
            $table->boolean('require_putaway')->default(false);
            $table->boolean('is_drop_ship')->default(false);
            $table->unsignedBigInteger('sales_order_id')->nullable(); // Drop-ship source

            // References
            $table->string('requisition_reference', 100)->nullable();
            $table->string('contract_reference', 100)->nullable();
            $table->unsignedBigInteger('blanket_po_id')->nullable(); // Parent blanket PO

            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('custom_fields')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'po_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'supplier_id']);
            $table->index('expected_delivery_date');
        });

        // ── Purchase Order Lines ─────────────────────────────────────────────
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_description', 500)->nullable();
            $table->string('supplier_product_code', 100)->nullable();

            // Quantities
            $table->decimal('ordered_qty', 19, 6);
            $table->decimal('received_qty', 19, 6)->default(0);
            $table->decimal('billed_qty', 19, 6)->default(0);
            $table->decimal('cancelled_qty', 19, 6)->default(0);
            $table->decimal('remaining_qty', 19, 6)->default(0);  // Computed
            $table->unsignedBigInteger('uom_id');
            $table->decimal('uom_to_base_factor', 19, 10)->default(1.0);

            // Pricing
            $table->decimal('unit_price', 19, 6);
            $table->decimal('discount_pct', 8, 4)->default(0);
            $table->decimal('discount_amount', 19, 6)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 19, 6)->default(0);
            $table->decimal('subtotal', 19, 6)->default(0);        // before tax
            $table->decimal('total', 19, 6)->default(0);           // after tax

            // Delivery
            $table->date('expected_delivery_date')->nullable();
            $table->unsignedBigInteger('delivery_location_id')->nullable();
            $table->string('status', 30)->default('pending');
            // pending | partial | received | billed | cancelled

            // Lot/serial expectations
            $table->boolean('expect_lots')->default(false);
            $table->boolean('expect_serials')->default(false);
            $table->integer('expiry_alert_days')->nullable();

            // Reference to blanket PO line
            $table->unsignedBigInteger('blanket_line_id')->nullable();

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        // ── Goods Receipts (Purchase Receipts / GRN) ─────────────────────────
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();  // Can receive without PO
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('receiving_location_id')->nullable();

            $table->string('grn_number', 100);        // Goods Receipt Note number
            $table->string('supplier_delivery_note', 150)->nullable();
            $table->string('supplier_invoice_number', 150)->nullable();

            $table->string('status', 30)->default('draft');
            // draft | in_progress | received | quality_check | put_away | done | cancelled

            $table->timestamp('receipt_date');
            $table->timestamp('validated_at')->nullable();

            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();

            // Quality check
            $table->boolean('quality_check_required')->default(false);
            $table->string('quality_check_status', 30)->nullable();
            $table->unsignedBigInteger('quality_checked_by')->nullable();
            $table->timestamp('quality_checked_at')->nullable();
            $table->text('quality_notes')->nullable();

            // Linked stock movement
            $table->unsignedBigInteger('stock_movement_id')->nullable();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'grn_number']);
            $table->index(['purchase_order_id']);
        });

        // ── Purchase Receipt Lines ───────────────────────────────────────────
        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('po_line_id')->nullable();  // Matched PO line
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_description', 500)->nullable();

            // Lot/batch assignment on receipt
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->string('lot_number_input', 150)->nullable();      // For new lot creation
            $table->date('expiry_date_input')->nullable();
            $table->date('manufacture_date_input')->nullable();
            $table->string('supplier_lot_number', 150)->nullable();

            // Quantities
            $table->decimal('expected_qty', 19, 6)->nullable();
            $table->decimal('received_qty', 19, 6);
            $table->decimal('accepted_qty', 19, 6)->nullable();      // QC accepted
            $table->decimal('rejected_qty', 19, 6)->nullable();      // QC rejected
            $table->unsignedBigInteger('uom_id');

            // Location
            $table->unsignedBigInteger('receiving_location_id')->nullable();
            $table->unsignedBigInteger('putaway_location_id')->nullable();  // After put-away

            // Costing
            $table->decimal('unit_cost', 19, 6)->nullable();
            $table->decimal('total_cost', 19, 6)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();

            // Serial tracking
            $table->json('serial_numbers')->nullable(); // Array of serial numbers received

            $table->string('status', 30)->default('pending');
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('receipt_id')->references('id')->on('purchase_receipts')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('tracking_lots')->nullOnDelete();
        });

        // ── Landed Costs ─────────────────────────────────────────────────────
        // Additional costs allocated to received goods (freight, duty, insurance).
        // Affects inventory valuation layers.
        Schema::create('landed_costs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('reference_number', 100);
            $table->string('status', 30)->default('draft');
            // draft | validated | posted

            $table->unsignedBigInteger('vendor_id')->nullable();     // Cost provider
            $table->unsignedBigInteger('vendor_bill_id')->nullable(); // AP invoice

            $table->decimal('total_amount', 19, 6);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 20, 8)->default(1.0);

            $table->timestamp('cost_date');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_number']);
        });

        // ── Landed Cost Lines (what receipt is affected) ─────────────────────
        Schema::create('landed_cost_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('landed_cost_id');
            $table->unsignedBigInteger('receipt_id');
            $table->string('cost_type', 50)->default('freight');
            // freight | duty | insurance | handling | other

            $table->string('split_method', 30)->default('by_value');
            // by_value | by_qty | by_weight | by_volume | equal | manual

            $table->decimal('amount', 19, 6);
            $table->decimal('allocated_amount', 19, 6)->default(0);  // After split

            $table->timestamps();

            $table->foreign('landed_cost_id')->references('id')->on('landed_costs')->cascadeOnDelete();
            $table->foreign('receipt_id')->references('id')->on('purchase_receipts');
        });

        // ── Landed Cost Allocation (per receipt line / valuation layer) ──────
        Schema::create('landed_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('landed_cost_line_id');
            $table->unsignedBigInteger('receipt_line_id');
            $table->unsignedBigInteger('valuation_layer_id')->nullable();
            $table->decimal('allocated_amount', 19, 6);
            $table->decimal('basis_value', 19, 6)->nullable(); // Basis used in split
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landed_cost_allocations');
        Schema::dropIfExists('landed_cost_lines');
        Schema::dropIfExists('landed_costs');
        Schema::dropIfExists('purchase_receipt_lines');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
