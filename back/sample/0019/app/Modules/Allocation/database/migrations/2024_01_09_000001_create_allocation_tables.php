<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allocation Module — Stock reservation and fulfillment allocation engine.
 *
 * User-configurable:
 *   - Allocation algorithm: standard | priority | fair_share | manual | wave | zone | cluster
 *   - Stock rotation strategy: FIFO | LIFO | FEFO | LEFO | FMFO | SLED | manual
 *   - Picking strategies: single | batch | wave | zone | cluster
 *   - Priority rules: customer tier, order date, requested date, channel
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Allocation Settings (per warehouse/org) ─────────────────────────
        Schema::create('allocation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            // Primary allocation algorithm
            $table->string('algorithm', 30)->default('standard');
            // standard | priority | fair_share | manual | wave | zone | cluster | round_robin

            // Stock rotation (order in which lots/locations are picked)
            $table->string('rotation_strategy', 30)->default('fifo');
            // fifo | lifo | fefo | lefo | fmfo | sled | fefo_fifo | manual

            // Allocation priority factors (JSON ordered list of criteria)
            $table->json('priority_factors')->nullable();
            /*
              [
                {"factor": "order_date", "direction": "asc"},
                {"factor": "customer_tier", "direction": "desc"},
                {"factor": "requested_delivery", "direction": "asc"},
                {"factor": "channel", "values": {"b2b": 1, "retail": 2, "web": 3}}
              ]
            */

            // Safety stock reservation
            $table->boolean('reserve_safety_stock')->default(true);
            $table->boolean('allow_partial_allocation')->default(true);

            // Expiry margin (don't pick stock expiring within N days)
            $table->integer('expiry_pick_margin_days')->default(0);

            // Auto-allocation triggers
            $table->boolean('auto_allocate_on_confirmation')->default(true);
            $table->boolean('auto_deallocate_on_cancellation')->default(true);
            $table->integer('reservation_expiry_hours')->nullable(); // Auto-release after N hours

            $table->json('custom_settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_id', 'warehouse_id']);
        });

        // ── Allocation Rules (configurable priority rules) ───────────────────
        Schema::create('allocation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();

            // When this rule applies (conditions — all nullable = always)
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_tier', 50)->nullable();         // vip|gold|silver|standard
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('order_type', 30)->nullable();            // standard|urgent|export
            $table->string('channel', 50)->nullable();               // b2b|retail|web|pos

            // What to apply
            $table->string('action', 30)->default('priority_boost');
            // priority_boost | priority_override | location_preference
            // | lot_preference | reserve_qty | block_allocation

            $table->json('action_config')->nullable();
            /*
              For priority_boost: {"boost": 10}
              For location_preference: {"location_ids": [12, 15], "strictly": false}
              For lot_preference: {"prefer_expiry_within_days": 30, "prefer_supplier_id": 5}
              For reserve_qty: {"qty": 100, "uom_id": 3}
            */

            $table->integer('priority')->default(10);  // Lower = applied first
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ── Stock Reservations ───────────────────────────────────────────────
        // Soft reservation of stock against orders.
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();

            // What is reserved
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('uom_id');

            // Quantities
            $table->decimal('reserved_qty', 19, 6);
            $table->decimal('allocated_qty', 19, 6)->default(0);   // Hard-allocated (in pick)
            $table->decimal('picked_qty', 19, 6)->default(0);      // Actually picked
            $table->decimal('delivered_qty', 19, 6)->default(0);   // Delivered

            // For whom (polymorphic — can be SO line, transfer, production, etc.)
            $table->string('reservable_type', 100);
            $table->unsignedBigInteger('reservable_id');

            // Reservation context
            $table->string('reservation_type', 30)->default('sales_order');
            // sales_order | production | transfer | manual | safety_stock | rental

            $table->string('status', 30)->default('reserved');
            // reserved | partially_picked | fully_picked | fulfilled | cancelled | expired

            $table->timestamp('reserved_at');
            $table->timestamp('expires_at')->nullable();    // Auto-release reservation
            $table->timestamp('fulfilled_at')->nullable();

            $table->integer('priority')->default(10);       // Allocation priority
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['reservable_type', 'reservable_id']);
            $table->index(['tenant_id', 'product_id', 'warehouse_id', 'status']);
            $table->index(['lot_id', 'status']);
        });

        // ── Wave Rules (for wave picking) ────────────────────────────────────
        Schema::create('wave_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name', 150);
            $table->string('wave_type', 30)->default('time_based');
            // time_based | volume_based | order_count | manual | zone_based

            // Wave release criteria
            $table->json('criteria')->nullable();
            /*
              {
                "max_orders_per_wave": 20,
                "max_lines_per_wave": 100,
                "max_weight_kg": 500,
                "include_channels": ["b2b", "retail"],
                "carrier_groups": ["FedEx", "UPS"],
                "scheduled_times": ["08:00", "12:00", "16:00"]
              }
            */

            $table->boolean('group_by_carrier')->default(false);
            $table->boolean('group_by_zone')->default(false);
            $table->boolean('group_by_customer')->default(false);
            $table->string('picking_strategy', 30)->default('discrete');
            // discrete | batch | cluster | zone

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Allocation Logs ─────────────────────────────────────────────────
        // Immutable log of every allocation decision.
        Schema::create('allocation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('event', 50);
            // allocated | deallocated | partial | failed | override | expired

            $table->string('reservable_type', 100);
            $table->unsignedBigInteger('reservable_id');
            $table->unsignedBigInteger('reservation_id')->nullable();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();

            $table->decimal('requested_qty', 19, 6);
            $table->decimal('allocated_qty', 19, 6);
            $table->decimal('failed_qty', 19, 6)->default(0);

            $table->string('algorithm_used', 30)->nullable();
            $table->string('rotation_used', 30)->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->json('applied_rules')->nullable();   // Which allocation rules fired

            $table->unsignedBigInteger('triggered_by')->nullable();  // User or system
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['reservable_type', 'reservable_id']);
            $table->index('occurred_at');
        });

        // ── Safety Stock Reservations (dedicate stock per product/warehouse) ─
        Schema::create('safety_stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('safety_stock_qty', 19, 6);
            $table->decimal('current_reserved_qty', 19, 6)->default(0);  // Computed
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'variant_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_stock_reservations');
        Schema::dropIfExists('allocation_logs');
        Schema::dropIfExists('wave_rules');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('allocation_rules');
        Schema::dropIfExists('allocation_settings');
    }
};
