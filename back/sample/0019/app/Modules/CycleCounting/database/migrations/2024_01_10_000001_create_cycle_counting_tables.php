<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CycleCounting Module — Inventory audit and cycle counting system.
 *
 * User-configurable methods:
 *   - ABC Analysis (count A items more frequently than B/C)
 *   - Periodic (full warehouse count on schedule)
 *   - Continuous (count a subset daily/weekly)
 *   - Location-based (count by bin/shelf/zone)
 *   - Zero-balance verification
 *   - Random sampling
 *   - Discrepancy-triggered recounts
 *
 * Full audit trail: expected vs counted vs variance → approval → adjustment.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── ABC Classification Rules ─────────────────────────────────────────
        // Classify products into A/B/C tiers by value, velocity, or risk.
        Schema::create('abc_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();

            $table->string('abc_class', 5)->default('C');  // A | B | C | D | X | Y | Z
            $table->string('classification_basis', 30)->default('value');
            // value | velocity | value_velocity | risk | manual

            $table->decimal('annual_usage_value', 19, 6)->nullable();
            $table->decimal('usage_velocity', 19, 6)->nullable();  // Moves per period
            $table->decimal('cumulative_pct', 8, 4)->nullable();   // % of total value

            // Counting frequency per class
            $table->integer('count_frequency_days')->default(365);
            // A=30, B=90, C=180 (example)

            $table->date('classified_date');
            $table->date('next_review_date')->nullable();
            $table->unsignedBigInteger('classified_by')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'variant_id', 'warehouse_id'], 'abc_class_unique');
        });

        // ── Cycle Count Plans ────────────────────────────────────────────────
        Schema::create('cycle_count_plans', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');

            $table->string('plan_name', 150);
            $table->text('description')->nullable();

            $table->string('count_method', 30)->default('abc');
            // abc | periodic | continuous | location_based | zero_balance | random | discrepancy_triggered

            // Scope
            $table->json('included_abc_classes')->nullable();    // ["A","B"] or null=all
            $table->json('included_categories')->nullable();     // category_ids
            $table->json('included_locations')->nullable();      // location_ids
            $table->json('excluded_locations')->nullable();      // e.g. quarantine zones
            $table->json('included_product_types')->nullable();  // product_type_ids

            // Schedule
            $table->string('frequency', 30)->default('monthly');
            // daily | weekly | biweekly | monthly | quarterly | annual | on_demand
            $table->json('schedule_days')->nullable();  // e.g. [1,15] = 1st and 15th of month
            $table->time('count_time')->nullable();

            // Auto-session generation
            $table->boolean('auto_generate_sessions')->default(true);
            $table->integer('days_before_due_to_generate')->default(3);
            $table->integer('session_duration_hours')->default(8);

            // Blind counting (counter doesn't see system qty)
            $table->boolean('blind_count')->default(true);
            $table->boolean('double_blind_count')->default(false); // Two independent counters
            $table->boolean('require_recount_on_variance')->default(true);
            $table->decimal('variance_threshold_pct', 8, 4)->default(2.00); // Trigger recount
            $table->decimal('variance_threshold_value', 19, 6)->nullable(); // Or value-based

            // Approval
            $table->boolean('require_approval_for_adjustment')->default(true);
            $table->decimal('auto_approve_below_value', 19, 6)->nullable();
            $table->json('approver_user_ids')->nullable();

            $table->boolean('is_active')->default(true);
            $table->date('plan_start_date')->nullable();
            $table->date('plan_end_date')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Cycle Count Sessions ─────────────────────────────────────────────
        Schema::create('cycle_count_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('organization_id')->nullable();

            $table->string('session_number', 100);
            $table->string('session_type', 30)->default('cycle_count');
            // cycle_count | physical_inventory | spot_check | discrepancy_recount | zero_balance

            $table->string('status', 30)->default('draft');
            // draft | in_progress | pending_approval | approved | adjusted | cancelled

            $table->timestamp('scheduled_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('adjusted_at')->nullable();

            // Responsible
            $table->unsignedBigInteger('count_manager_id')->nullable();
            $table->json('counter_user_ids')->nullable();  // Users assigned to count
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('adjusted_by')->nullable();

            // Summary metrics (computed)
            $table->integer('total_items_to_count')->default(0);
            $table->integer('total_items_counted')->default(0);
            $table->integer('items_with_variance')->default(0);
            $table->decimal('total_variance_value', 19, 6)->default(0);
            $table->decimal('accuracy_rate_pct', 8, 4)->nullable();

            // Stock freeze during counting
            $table->boolean('freeze_stock_during_count')->default(false);
            $table->timestamp('stock_freeze_start')->nullable();
            $table->timestamp('stock_freeze_end')->nullable();

            // Linked adjustment
            $table->unsignedBigInteger('stock_adjustment_id')->nullable();

            $table->text('notes')->nullable();
            $table->text('approval_notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'session_number']);
        });

        // ── Cycle Count Items (count sheet lines) ────────────────────────────
        Schema::create('cycle_count_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('uom_id');

            // System quantities (from stock_levels at session start)
            $table->decimal('system_qty', 19, 6)->default(0);      // Expected
            $table->decimal('system_unit_cost', 19, 6)->default(0);
            $table->decimal('system_total_value', 19, 6)->default(0);

            // Counted quantities (blind count)
            $table->decimal('counted_qty', 19, 6)->nullable();      // null = not yet counted
            $table->decimal('counted_qty_2', 19, 6)->nullable();    // Second counter (double-blind)
            $table->decimal('reconciled_qty', 19, 6)->nullable();   // Final agreed qty

            // Variance (computed)
            $table->decimal('variance_qty', 19, 6)->nullable();     // counted - system
            $table->decimal('variance_value', 19, 6)->nullable();   // variance × unit_cost
            $table->decimal('variance_pct', 8, 4)->nullable();

            // Status per line
            $table->string('status', 30)->default('pending');
            // pending | in_progress | counted | recount_required | reconciled | approved | adjusted

            $table->boolean('requires_recount')->default(false);
            $table->integer('recount_number')->default(0);

            // Who counted
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->unsignedBigInteger('counted_by_2')->nullable();  // Second counter
            $table->timestamp('counted_at_2')->nullable();

            $table->string('discrepancy_reason', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('cycle_count_sessions')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('tracking_lots')->nullOnDelete();
            $table->index(['session_id', 'status']);
            $table->index(['product_id', 'location_id']);
        });

        // ── Count History (recount trail) ────────────────────────────────────
        Schema::create('cycle_count_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('count_item_id');
            $table->integer('attempt_number')->default(1);
            $table->decimal('counted_qty', 19, 6);
            $table->unsignedBigInteger('counted_by');
            $table->timestamp('counted_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('count_item_id')->references('id')->on('cycle_count_items')->cascadeOnDelete();
        });

        // ── Inventory Discrepancy Reports ────────────────────────────────────
        Schema::create('inventory_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('count_item_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('uom_id');

            $table->decimal('system_qty', 19, 6);
            $table->decimal('counted_qty', 19, 6);
            $table->decimal('variance_qty', 19, 6);
            $table->decimal('variance_value', 19, 6);
            $table->decimal('unit_cost', 19, 6);

            $table->string('discrepancy_type', 30)->nullable();
            // over | short | missing | extra | lot_mismatch | location_mismatch

            $table->string('status', 30)->default('open');
            // open | under_investigation | approved | rejected | adjusted

            $table->string('root_cause', 100)->nullable();
            // theft | damage | miscounting | system_error | receiving_error | shipping_error | other

            $table->text('investigation_notes')->nullable();
            $table->unsignedBigInteger('investigated_by')->nullable();
            $table->timestamp('investigated_at')->nullable();

            // Resolution
            $table->string('resolution', 30)->nullable();
            // adjust_inventory | no_action | further_investigation | write_off
            $table->decimal('adjustment_qty', 19, 6)->nullable();
            $table->unsignedBigInteger('adjustment_id')->nullable(); // Linked stock adjustment
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'warehouse_id', 'status']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_discrepancies');
        Schema::dropIfExists('cycle_count_history');
        Schema::dropIfExists('cycle_count_items');
        Schema::dropIfExists('cycle_count_sessions');
        Schema::dropIfExists('cycle_count_plans');
        Schema::dropIfExists('abc_classifications');
    }
};
