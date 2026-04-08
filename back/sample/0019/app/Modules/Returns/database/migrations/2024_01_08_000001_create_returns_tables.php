<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Returns Module — Complete return management system.
 *
 * Covers:
 *   - Customer/Sales Returns (RMA → Receipt → Inspection → Disposition)
 *   - Supplier/Purchase Returns (Return to Vendor / RTV)
 *   - Return types: full, partial, with/without original lot/serial
 *   - Dispositions: restock, scrap, quarantine, refurbish, donate, return-to-supplier
 *   - Valuation impact: reversal of FIFO/LIFO layers, AVCO recalculation
 *   - Credit memo / debit note generation
 *   - Restocking fees, condition grading, quality inspection
 *
 * All returns are fully traceable and auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Return Reason Codes ─────────────────────────────────────────────
        Schema::create('return_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('return_type', 30)->default('any');
            // sales_return | purchase_return | any
            $table->boolean('requires_inspection')->default(false);
            $table->boolean('auto_restock')->default(false);
            $table->string('default_disposition', 30)->nullable();
            // restock | scrap | quarantine | refurbish | return_to_supplier
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        // ── Return Merchandise Authorizations (Customer Returns) ─────────────
        Schema::create('return_merchandise_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->string('rma_number', 100);
            $table->string('return_type', 30)->default('full');
            // full | partial | exchange | credit_only

            // Source reference
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->unsignedBigInteger('delivery_order_id')->nullable();
            $table->string('customer_order_ref', 150)->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('contact_id')->nullable();

            $table->string('status', 30)->default('draft');
            // draft | approved | received | inspecting | processed | completed | rejected | cancelled

            // Dates
            $table->date('request_date');
            $table->date('approved_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->date('return_deadline')->nullable(); // After this, RMA is void

            // Approval
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Financial
            $table->decimal('refund_amount', 19, 6)->default(0);
            $table->decimal('restocking_fee', 19, 6)->default(0);
            $table->decimal('restocking_fee_pct', 8, 4)->default(0);
            $table->decimal('net_refund_amount', 19, 6)->default(0);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('refund_method', 30)->nullable();
            // original_payment | store_credit | bank_transfer | exchange | voucher
            $table->unsignedBigInteger('credit_memo_id')->nullable();

            // Return address (customer's return shipping)
            $table->string('return_from_name', 255)->nullable();
            $table->string('return_from_address', 500)->nullable();
            $table->string('return_carrier', 100)->nullable();
            $table->string('return_tracking_number', 150)->nullable();
            $table->string('return_label_path', 500)->nullable(); // Pre-paid return label

            // Linked stock
            $table->unsignedBigInteger('return_stock_movement_id')->nullable();
            $table->unsignedBigInteger('return_location_id')->nullable();

            $table->text('customer_notes')->nullable();  // Customer's stated reason
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'rma_number']);
            $table->index(['tenant_id', 'customer_id', 'status']);
            $table->index('sales_order_id');
        });

        // ── RMA Lines ────────────────────────────────────────────────────────
        Schema::create('rma_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rma_id');
            $table->unsignedBigInteger('sales_order_line_id')->nullable();
            $table->unsignedBigInteger('delivery_order_line_id')->nullable();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_description', 500)->nullable();

            // Original tracking details
            $table->unsignedBigInteger('original_lot_id')->nullable();       // Return WITH original lot
            $table->unsignedBigInteger('original_serial_id')->nullable();    // Return WITH original serial
            $table->boolean('has_original_lot')->default(false);     // Customer has original tracking
            $table->boolean('has_original_serial')->default(false);

            // Quantities
            $table->decimal('requested_qty', 19, 6);
            $table->decimal('approved_qty', 19, 6)->nullable();
            $table->decimal('received_qty', 19, 6)->default(0);
            $table->decimal('restocked_qty', 19, 6)->default(0);
            $table->decimal('scrapped_qty', 19, 6)->default(0);
            $table->unsignedBigInteger('uom_id');

            // Return reason
            $table->unsignedBigInteger('return_reason_id')->nullable();
            $table->text('reason_description')->nullable();

            // Pricing at time of original sale
            $table->decimal('original_unit_price', 19, 6)->nullable();
            $table->decimal('original_unit_cost', 19, 6)->nullable();
            $table->decimal('refund_unit_price', 19, 6)->nullable();   // May differ if restocking fee
            $table->decimal('refund_amount', 19, 6)->nullable();

            // Condition assessment (after inspection)
            $table->string('returned_condition', 30)->nullable();
            // new | like_new | good | fair | damaged | unusable
            $table->string('disposition', 30)->nullable();
            // restock | scrap | quarantine | refurbish | return_to_supplier | donate

            // Inspection
            $table->text('inspection_notes')->nullable();
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->string('inspection_result', 30)->nullable(); // pass | fail | partial

            // Post-processing
            $table->unsignedBigInteger('restocked_lot_id')->nullable();    // New lot if re-lotted
            $table->unsignedBigInteger('restock_location_id')->nullable(); // Where restocked
            $table->decimal('restock_unit_cost', 19, 6)->nullable();       // Restocked cost (may differ)

            $table->string('status', 30)->default('pending');
            // pending | received | inspecting | processed | cancelled

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('rma_id')->references('id')->on('return_merchandise_authorizations')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('original_lot_id')->references('id')->on('tracking_lots')->nullOnDelete();
        });

        // ── Supplier Return Orders (Return to Vendor / RTV) ──────────────────
        Schema::create('supplier_return_orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');

            $table->string('rtv_number', 100);
            $table->unsignedBigInteger('supplier_id');
            $table->string('supplier_reference', 150)->nullable();  // Supplier's RMA/RTV number
            $table->string('return_type', 30)->default('defective');
            // defective | overstock | wrong_item | quality_fail | expired | recall | other

            // Source references
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('purchase_receipt_id')->nullable();

            $table->string('status', 30)->default('draft');
            // draft | approved_by_supplier | shipped | received_by_supplier | credited | done | cancelled

            $table->date('return_date');
            $table->date('expected_credit_date')->nullable();

            // Financial
            $table->decimal('total_return_value', 19, 6)->default(0);
            $table->decimal('credit_received', 19, 6)->default(0);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('debit_note_id')->nullable();  // AP debit note / credit memo

            // Shipping
            $table->string('carrier_name', 100)->nullable();
            $table->string('tracking_number', 150)->nullable();
            $table->decimal('return_shipping_cost', 19, 6)->nullable();
            $table->string('incoterms', 20)->nullable();

            $table->unsignedBigInteger('return_stock_movement_id')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'rtv_number']);
        });

        // ── Supplier Return Lines ─────────────────────────────────────────────
        Schema::create('supplier_return_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_return_order_id');
            $table->unsignedBigInteger('purchase_order_line_id')->nullable();
            $table->unsignedBigInteger('receipt_line_id')->nullable();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();

            // Original tracking
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('source_location_id')->nullable();

            $table->decimal('return_qty', 19, 6);
            $table->unsignedBigInteger('uom_id');

            $table->string('return_reason', 50)->nullable();
            $table->text('reason_description')->nullable();

            // Condition
            $table->string('condition', 30)->nullable();
            $table->decimal('original_unit_cost', 19, 6)->nullable();   // What we paid
            $table->decimal('credit_unit_price', 19, 6)->nullable();    // Supplier credit per unit
            $table->decimal('credit_amount', 19, 6)->nullable();        // Total credit for this line

            // Valuation reversal info
            $table->string('costing_method', 30)->nullable();
            $table->unsignedBigInteger('original_valuation_layer_id')->nullable(); // Layer to reverse

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supplier_return_order_id')->references('id')->on('supplier_return_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });

        // ── Return Valuation Adjustments ─────────────────────────────────────
        // Tracks impact of returns on inventory valuation layers.
        Schema::create('return_valuation_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();

            $table->string('return_type', 30); // sales_return | supplier_return
            $table->unsignedBigInteger('return_id');
            $table->unsignedBigInteger('return_line_id');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();

            $table->string('costing_method', 30); // fifo|lifo|avco|standard
            $table->string('adjustment_type', 30); // layer_reversal | new_layer | avco_recalc | standard_adj

            // For layer reversal (FIFO/LIFO)
            $table->unsignedBigInteger('original_layer_id')->nullable();
            $table->decimal('reversed_qty', 19, 6)->nullable();
            $table->decimal('reversed_unit_cost', 19, 6)->nullable();
            $table->decimal('reversed_value', 19, 6)->nullable();

            // New layer created for restocked item
            $table->unsignedBigInteger('new_layer_id')->nullable();
            $table->decimal('new_unit_cost', 19, 6)->nullable();

            // AVCO recalculation impact
            $table->decimal('avco_before', 19, 6)->nullable();
            $table->decimal('avco_after', 19, 6)->nullable();
            $table->decimal('avco_variance', 19, 6)->nullable();

            $table->decimal('total_value_impact', 19, 6)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['return_type', 'return_id']);
        });

        // ── Quality Inspection Records (for returns and receipt QC) ──────────
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('inspectable_type', 100); // RmaLine | ReceiptLine | StockMovementLine
            $table->unsignedBigInteger('inspectable_id');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();

            $table->decimal('inspected_qty', 19, 6);
            $table->decimal('passed_qty', 19, 6)->default(0);
            $table->decimal('failed_qty', 19, 6)->default(0);

            $table->string('overall_result', 30)->default('pending');
            // pending | pass | fail | partial_pass | conditional_release

            // Inspection checklist results (configurable per product/type)
            $table->json('checklist_results')->nullable();
            /*
              [
                {"criterion": "Physical Damage", "result": "pass", "notes": ""},
                {"criterion": "Seal Intact",     "result": "fail", "notes": "Seal broken"},
                {"criterion": "Weight Check",    "result": "pass", "notes": "Within tolerance"}
              ]
            */
            $table->string('disposition', 30)->nullable();
            // restock | scrap | quarantine | refurbish | return_to_supplier | downgrade

            $table->unsignedBigInteger('inspected_by');
            $table->timestamp('inspection_date');
            $table->text('notes')->nullable();
            $table->string('photo_evidence_path', 500)->nullable();

            $table->timestamps();
            $table->index(['inspectable_type', 'inspectable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_inspections');
        Schema::dropIfExists('return_valuation_adjustments');
        Schema::dropIfExists('supplier_return_lines');
        Schema::dropIfExists('supplier_return_orders');
        Schema::dropIfExists('rma_lines');
        Schema::dropIfExists('return_merchandise_authorizations');
        Schema::dropIfExists('return_reasons');
    }
};
