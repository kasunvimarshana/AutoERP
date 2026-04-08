<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales Module — Sales Orders → Pick → Pack → Ship workflow.
 * Multi-step delivery: single-step | two-step (pick+ship) | three-step (pick+pack+ship).
 * Supports: backorders, partial deliveries, drop-ship, consignment.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Sales Orders ─────────────────────────────────────────────────────
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->string('order_number', 100);
            $table->string('customer_reference', 150)->nullable();

            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('contact_id')->nullable();    // Ship-to contact
            $table->unsignedBigInteger('sales_person_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();

            $table->string('order_type', 30)->default('standard');
            // standard | return | quote | drop_ship | consignment | rental | subscription | service

            $table->string('status', 30)->default('draft');
            // draft | confirmed | processing | partial | delivered | invoiced | done | cancelled

            // Dates
            $table->date('order_date');
            $table->date('requested_delivery_date')->nullable();
            $table->date('confirmed_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->date('expiry_date')->nullable();          // Quote expiry

            // Shipping address (snapshot at time of order)
            $table->string('ship_to_name', 255)->nullable();
            $table->string('ship_to_address_line1', 255)->nullable();
            $table->string('ship_to_address_line2', 255)->nullable();
            $table->string('ship_to_city', 100)->nullable();
            $table->string('ship_to_state', 100)->nullable();
            $table->string('ship_to_postal_code', 20)->nullable();
            $table->string('ship_to_country_code', 5)->nullable();

            // Pricing
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 20, 8)->default(1.00000000);

            // Totals
            $table->decimal('subtotal', 19, 6)->default(0);
            $table->decimal('discount_amount', 19, 6)->default(0);
            $table->decimal('tax_amount', 19, 6)->default(0);
            $table->decimal('shipping_amount', 19, 6)->default(0);
            $table->decimal('total_amount', 19, 6)->default(0);
            $table->decimal('amount_invoiced', 19, 6)->default(0);
            $table->decimal('amount_paid', 19, 6)->default(0);

            // Payment
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->date('payment_due_date')->nullable();

            // Shipping
            $table->string('carrier_name', 100)->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->string('incoterms', 20)->nullable();

            // Drop-ship
            $table->boolean('is_drop_ship')->default(false);
            $table->unsignedBigInteger('drop_ship_supplier_id')->nullable();
            $table->unsignedBigInteger('drop_ship_po_id')->nullable();

            // Allocation
            $table->string('allocation_status', 30)->default('unallocated');
            // unallocated | partial | fully_allocated
            $table->string('picking_status', 30)->default('nothing_to_pick');
            // nothing_to_pick | waiting | ready | partial | done

            // Invoicing
            $table->string('invoice_policy', 30)->default('on_delivery');
            // on_order | on_delivery | milestone

            $table->unsignedBigInteger('invoice_address_id')->nullable();

            // Analytics
            $table->string('source_channel', 50)->nullable(); // web|pos|phone|b2b|api
            $table->string('campaign_reference', 100)->nullable();

            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('custom_fields')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'customer_id', 'status']);
            $table->index('requested_delivery_date');
        });

        // ── Sales Order Lines ────────────────────────────────────────────────
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_description', 500)->nullable();  // Editable snapshot

            // Quantities
            $table->decimal('ordered_qty', 19, 6);
            $table->decimal('delivered_qty', 19, 6)->default(0);
            $table->decimal('invoiced_qty', 19, 6)->default(0);
            $table->decimal('returned_qty', 19, 6)->default(0);
            $table->decimal('cancelled_qty', 19, 6)->default(0);
            $table->unsignedBigInteger('uom_id');
            $table->decimal('uom_to_base_factor', 19, 10)->default(1.0);

            // Pricing
            $table->decimal('unit_price', 19, 6);
            $table->decimal('discount_pct', 8, 4)->default(0);
            $table->decimal('discount_amount', 19, 6)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 19, 6)->default(0);
            $table->decimal('subtotal', 19, 6)->default(0);
            $table->decimal('total', 19, 6)->default(0);

            // Pricing margin
            $table->decimal('cost_price', 19, 6)->nullable();     // Snapshot cost
            $table->decimal('margin_amount', 19, 6)->nullable();
            $table->decimal('margin_pct', 8, 4)->nullable();

            // Delivery
            $table->date('requested_delivery_date')->nullable();
            $table->unsignedBigInteger('source_warehouse_id')->nullable();
            $table->unsignedBigInteger('source_location_id')->nullable();

            // Lot / serial specification
            $table->unsignedBigInteger('lot_id')->nullable();    // Specific lot requested
            $table->boolean('require_lot')->default(false);
            $table->boolean('require_serial')->default(false);

            $table->string('status', 30)->default('pending');
            // pending | confirmed | partial | delivered | cancelled

            $table->boolean('is_kit_component')->default(false); // Part of a kit
            $table->unsignedBigInteger('parent_line_id')->nullable(); // Kit header line

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->index('sales_order_id');
        });

        // ── Delivery Orders ──────────────────────────────────────────────────
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('customer_id');

            $table->string('delivery_number', 100);
            $table->string('delivery_type', 30)->default('outgoing');
            // outgoing | incoming(return) | internal

            $table->string('status', 30)->default('draft');
            // draft | waiting | ready | in_progress | done | cancelled

            $table->unsignedBigInteger('source_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();
            $table->unsignedBigInteger('stock_movement_id')->nullable();

            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedBigInteger('carrier_id')->nullable();
            $table->string('carrier_name', 100)->nullable();
            $table->string('tracking_number', 150)->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->decimal('shipping_cost', 19, 6)->nullable();

            // Backorder
            $table->unsignedBigInteger('backorder_of_id')->nullable();

            // Packing
            $table->boolean('packing_required')->default(false);
            $table->string('packing_status', 30)->nullable();
            $table->integer('total_packages')->default(0);
            $table->decimal('total_weight_kg', 12, 4)->nullable();
            $table->decimal('total_volume_cbm', 12, 4)->nullable();

            $table->string('pod_document_path', 500)->nullable(); // Proof of Delivery
            $table->timestamp('delivered_at')->nullable();
            $table->string('delivered_to_name', 150)->nullable();
            $table->string('delivered_to_signature', 500)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'delivery_number']);
            $table->index('sales_order_id');
        });

        // ── Delivery Order Lines ─────────────────────────────────────────────
        Schema::create('delivery_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->unsignedBigInteger('sales_order_line_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();

            // Lot/serial allocation
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('source_location_id')->nullable();

            $table->decimal('demand_qty', 19, 6);
            $table->decimal('done_qty', 19, 6)->default(0);
            $table->unsignedBigInteger('uom_id');

            $table->decimal('unit_cost', 19, 6)->nullable();
            $table->decimal('cogs_amount', 19, 6)->nullable();

            $table->string('status', 30)->default('pending');
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('delivery_order_id')->references('id')->on('delivery_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('lot_id')->references('id')->on('tracking_lots')->nullOnDelete();
        });

        // ── Pick Lists ───────────────────────────────────────────────────────
        Schema::create('pick_lists', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('pick_number', 100);
            $table->string('pick_type', 30)->default('single');
            // single | batch | wave | zone | cluster
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('delivery_order_ids')->nullable(); // Linked delivery orders
            $table->timestamps();

            $table->unique(['tenant_id', 'pick_number']);
        });

        // ── Pick List Lines (detailed picking instructions) ──────────────────
        Schema::create('pick_list_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pick_list_id');
            $table->unsignedBigInteger('delivery_order_line_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('source_location_id');
            $table->decimal('pick_qty', 19, 6);
            $table->decimal('picked_qty', 19, 6)->default(0);
            $table->unsignedBigInteger('uom_id');
            $table->integer('pick_sequence')->nullable(); // Optimized path
            $table->string('status', 30)->default('pending');
            $table->timestamp('picked_at')->nullable();
            $table->unsignedBigInteger('picked_by')->nullable();
            $table->timestamps();

            $table->foreign('pick_list_id')->references('id')->on('pick_lists')->cascadeOnDelete();
        });

        // ── Pack Operations ──────────────────────────────────────────────────
        Schema::create('pack_operations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_order_id');
            $table->string('pack_number', 100);
            $table->string('status', 30)->default('draft');
            $table->string('package_type', 30)->nullable(); // box | pallet | envelope | custom
            $table->decimal('gross_weight_kg', 12, 4)->nullable();
            $table->decimal('net_weight_kg', 12, 4)->nullable();
            $table->decimal('volume_cbm', 12, 4)->nullable();
            $table->string('tracking_number', 150)->nullable();
            $table->string('sscc', 25)->nullable(); // GS1 SSCC for pallet/carton
            $table->timestamp('packed_at')->nullable();
            $table->unsignedBigInteger('packed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_operations');
        Schema::dropIfExists('pick_list_lines');
        Schema::dropIfExists('pick_lists');
        Schema::dropIfExists('delivery_order_lines');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
