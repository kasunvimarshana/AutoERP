<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('retail');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->integer('payment_terms_days')->default(0);
            $table->string('tax_id')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('status')->default('active');
            $table->decimal('credit_limit', 20, 4)->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('sales_rep_id')->nullable();
            $table->string('order_number')->unique();
            $table->string('channel')->default('direct');
            // direct|pos|ecommerce|wholesale|api|marketplace|b2b
            $table->string('external_order_id')->nullable();
            $table->string('status')->default('draft');
            // draft|confirmed|picking|partially_picked|picked|packing|packed|shipped|delivered|cancelled|on_hold
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('payment_status')->default('unpaid');
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 16, 6)->default(1);
            $table->date('order_date');
            $table->date('requested_delivery_date')->nullable();
            $table->date('promised_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('shipping_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('amount_paid', 20, 4)->default(0);
            $table->string('allocation_algorithm', 30)->nullable();
            $table->integer('priority')->default(0);
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->integer('line_number')->nullable();
            $table->decimal('quantity_ordered', 20, 4);
            $table->decimal('quantity_allocated', 20, 4)->default(0);
            $table->decimal('quantity_picked', 20, 4)->default(0);
            $table->decimal('quantity_packed', 20, 4)->default(0);
            $table->decimal('quantity_shipped', 20, 4)->default(0);
            $table->decimal('quantity_invoiced', 20, 4)->default(0);
            $table->decimal('quantity_returned', 20, 4)->default(0);
            $table->decimal('quantity_cancelled', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('discount_percentage', 8, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4);
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->json('component_details')->nullable();
            $table->timestamps();
        });

        // ── Stock Allocations (all 8 algorithms recorded) ─────────────────────
        Schema::create('stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('sales_order_line_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->unsignedBigInteger('allocated_by')->index();
            $table->string('allocation_type');
            // hard|soft|wave|zone|batch_pick|cluster_pick
            $table->string('algorithm_used');
            // strict_reservation|soft_reservation|fair_share|priority_based
            // wave_picking|zone_picking|batch_picking|cluster_picking
            $table->string('status')->default('active');
            // active|fulfilled|cancelled|expired
            $table->decimal('quantity_allocated', 20, 4);
            $table->decimal('quantity_fulfilled', 20, 4)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'status']);
            $table->index(['sales_order_id', 'status']);
        });

        // ── Pick Lists ────────────────────────────────────────────────────────
        Schema::create('pick_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->string('pick_number')->unique();
            $table->string('type')->default('single');
            // single|wave|zone|batch|cluster
            $table->string('status')->default('pending');
            $table->integer('priority')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pick_list_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_list_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sales_order_id')->index();
            $table->unsignedBigInteger('sales_order_line_id')->index();
            $table->unsignedBigInteger('stock_allocation_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->decimal('quantity_to_pick', 20, 4);
            $table->decimal('quantity_picked', 20, 4)->default(0);
            $table->string('status')->default('pending');
            $table->string('short_reason')->nullable();
            $table->integer('pick_sequence')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->timestamps();
        });

        // ── Shipments ─────────────────────────────────────────────────────────
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->string('shipment_number')->unique();
            $table->string('status')->default('pending');
            $table->string('carrier')->nullable();
            $table->string('service_level')->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('shipping_cost', 20, 4)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->json('shipping_address')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sales_order_id')->index();
            $table->unsignedBigInteger('sales_order_line_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->decimal('quantity', 20, 4);
            $table->timestamps();
        });

        // ── Returns (RMA) ─────────────────────────────────────────────────────
        Schema::create('return_authorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->string('rma_number')->unique();
            $table->string('status')->default('pending');
            $table->string('reason_category');
            $table->text('customer_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('resolution_type')->nullable();
            $table->decimal('refund_amount', 20, 4)->nullable();
            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->date('received_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('return_authorization_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_authorization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sales_order_line_id')->nullable();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->decimal('quantity_requested', 20, 4);
            $table->decimal('quantity_received', 20, 4)->default(0);
            $table->decimal('quantity_restocked', 20, 4)->default(0);
            $table->decimal('quantity_scrapped', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('refund_amount', 20, 4)->nullable();
            $table->string('condition')->nullable();
            $table->string('disposition')->nullable();
            // restock|quarantine|scrap|return_to_supplier|repair
            $table->text('inspection_notes')->nullable();
            $table->timestamps();
        });

        // ── Stock Transfers ───────────────────────────────────────────────────
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('source_warehouse_id')->index();
            $table->unsignedBigInteger('destination_warehouse_id')->index();
            $table->unsignedBigInteger('source_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('transfer_number')->unique();
            $table->string('type')->default('warehouse');
            $table->string('status')->default('draft');
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('shipping_cost', 20, 4)->nullable();
            $table->date('transfer_date');
            $table->date('expected_arrival_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_requested', 20, 4);
            $table->decimal('quantity_shipped', 20, 4)->default(0);
            $table->decimal('quantity_received', 20, 4)->default(0);
            $table->decimal('quantity_discrepancy', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('status')->default('pending');
            $table->string('discrepancy_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Stock Adjustments ─────────────────────────────────────────────────
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('adjustment_number')->unique();
            $table->string('type');
            // positive|negative|revaluation|reclassification|write_off|write_up
            $table->string('reason_category');
            $table->string('status')->default('draft');
            $table->date('adjustment_date');
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_system', 20, 4)->nullable();
            $table->decimal('quantity_actual', 20, 4)->nullable();
            $table->decimal('quantity_adjusted', 20, 4);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('cost_impact', 20, 4)->nullable();
            $table->string('new_cost_price')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Physical Counts ───────────────────────────────────────────────────
        Schema::create('physical_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('count_number')->unique();
            $table->string('type')->default('full');
            // full|partial|cycle|spot_check
            $table->string('status')->default('planning');
            $table->string('cycle_count_method')->nullable();
            // abc|velocity|random|zone|category
            $table->json('scope_filters')->nullable();
            $table->boolean('freeze_inventory')->default(false);
            $table->boolean('blind_count')->default(true);
            $table->date('scheduled_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('physical_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_count_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->unsignedBigInteger('recounted_by')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_system', 20, 4)->default(0);
            $table->decimal('quantity_counted', 20, 4)->nullable();
            $table->decimal('quantity_recounted', 20, 4)->nullable();
            $table->decimal('quantity_variance', 20, 4)->nullable();
            $table->boolean('has_discrepancy')->default(false);
            $table->boolean('requires_recount')->default(false);
            $table->string('status')->default('pending');
            $table->timestamp('counted_at')->nullable();
            $table->timestamp('recounted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Bills of Materials + Production ───────────────────────────────────
        Schema::create('bills_of_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->string('bom_number')->unique();
            $table->string('name');
            $table->string('version')->default('1.0');
            $table->string('type')->default('manufacturing');
            // manufacturing|assembly|phantom|kit|rework
            $table->decimal('output_quantity', 20, 4)->default(1);
            $table->decimal('scrap_percentage', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bom_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bills_of_materials')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity', 20, 4);
            $table->decimal('scrap_percentage', 8, 4)->default(0);
            $table->boolean('is_critical')->default(false);
            $table->string('operation_step')->nullable();
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('bom_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->string('production_number')->unique();
            $table->string('status')->default('draft');
            $table->decimal('quantity_planned', 20, 4);
            $table->decimal('quantity_produced', 20, 4)->default(0);
            $table->decimal('quantity_scrapped', 20, 4)->default(0);
            $table->unsignedBigInteger('output_batch_id')->nullable();
            $table->unsignedBigInteger('output_lot_id')->nullable();
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->decimal('planned_cost', 20, 4)->nullable();
            $table->decimal('actual_cost', 20, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('production_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_required', 20, 4);
            $table->decimal('quantity_issued', 20, 4)->default(0);
            $table->decimal('quantity_returned', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // ── Reorder Rules & Alerts ────────────────────────────────────────────
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('preferred_supplier_id')->nullable();
            $table->string('method')->default('min_max');
            // min_max|fixed_quantity|economic_order_qty|days_of_supply|demand_driven
            $table->decimal('min_qty', 14, 4)->nullable();
            $table->decimal('max_qty', 14, 4)->nullable();
            $table->decimal('reorder_qty', 14, 4)->nullable();
            $table->integer('days_of_supply')->nullable();
            $table->decimal('safety_stock', 14, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_generate_po')->default(false);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('alert_type');
            // low_stock|out_of_stock|overstock|expiring_soon|expired|negative_stock|reorder_point
            $table->decimal('current_quantity', 20, 4)->nullable();
            $table->decimal('threshold_quantity', 20, 4)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('active');
            // active|acknowledged|resolved|suppressed
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'alert_type']);
        });

        // ── Inventory Snapshots ───────────────────────────────────────────────
        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('period');
            $table->string('snapshot_type')->default('monthly');
            $table->string('valuation_method', 30);
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->decimal('total_items', 14, 0)->default(0);
            $table->decimal('total_quantity', 20, 4)->default(0);
            $table->decimal('total_cost_value', 20, 4)->default(0);
            $table->decimal('total_retail_value', 20, 4)->default(0);
            $table->timestamp('generated_at');
            $table->unsignedBigInteger('generated_by');
            $table->timestamps();
        });

        Schema::create('inventory_snapshot_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_snapshot_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('average_cost', 20, 6)->default(0);
            $table->decimal('total_cost_value', 20, 4)->default(0);
            $table->decimal('selling_price', 20, 4)->nullable();
            $table->decimal('total_retail_value', 20, 4)->nullable();
            $table->timestamps();
        });

        // ── Product Classifications (ABC/XYZ/Velocity) ────────────────────────
        Schema::create('product_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('abc_class')->nullable();
            $table->string('xyz_class')->nullable();
            $table->string('velocity_class')->nullable();
            $table->decimal('annual_demand_value', 20, 4)->nullable();
            $table->decimal('annual_demand_qty', 20, 4)->nullable();
            $table->decimal('demand_variability', 8, 4)->nullable();
            $table->string('period');
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'period'], 'pc_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_classifications');
        Schema::dropIfExists('inventory_snapshot_lines');
        Schema::dropIfExists('inventory_snapshots');
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('reorder_rules');
        Schema::dropIfExists('production_order_lines');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bom_components');
        Schema::dropIfExists('bills_of_materials');
        Schema::dropIfExists('physical_count_items');
        Schema::dropIfExists('physical_counts');
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('return_authorization_lines');
        Schema::dropIfExists('return_authorizations');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('pick_list_lines');
        Schema::dropIfExists('pick_lists');
        Schema::dropIfExists('stock_allocations');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('customers');
    }
};
