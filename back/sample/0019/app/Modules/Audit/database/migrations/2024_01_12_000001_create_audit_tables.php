<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit Module — Full system-wide audit trail.
 *
 * Captures:
 *   - All model CREATE / UPDATE / DELETE events
 *   - Inventory movements and valuation changes
 *   - User authentication events
 *   - Configuration changes (settings, rules)
 *   - Approval events
 *   - Data access (sensitive items)
 *   - System events (batch jobs, automated actions)
 *
 * Designed to satisfy:
 *   - GxP / FDA 21 CFR Part 11 (pharma)
 *   - ISO 9001 quality management
 *   - Financial audit requirements
 *   - SOX compliance
 *   - GDPR data access logging
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Audit Trails (immutable event log) ──────────────────────────────
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();

            // Who
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 150)->nullable();           // Snapshot
            $table->string('user_email', 255)->nullable();          // Snapshot
            $table->string('user_role', 100)->nullable();           // Snapshot
            $table->string('performed_by_type', 50)->default('user'); // user | system | api | cron
            $table->string('api_client_id', 100)->nullable();       // For API events
            $table->string('ip_address', 50)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('session_id', 150)->nullable();

            // What
            $table->string('event', 50);
            // created | updated | deleted | restored | viewed | exported | imported
            // | approved | rejected | status_changed | setting_changed | login | logout
            // | stock_in | stock_out | adjustment | revaluation | period_closed

            $table->string('auditable_type', 150);      // Model class name
            $table->unsignedBigInteger('auditable_id'); // Model primary key
            $table->string('auditable_ref', 150)->nullable(); // Human-readable ref (PO#, etc.)

            // Changes (old vs new state)
            $table->json('old_values')->nullable();    // Before state
            $table->json('new_values')->nullable();    // After state
            $table->json('changed_fields')->nullable(); // List of changed field names
            $table->json('metadata')->nullable();       // Additional context

            // Context (what action triggered this)
            $table->string('action_description', 500)->nullable();   // Human-readable
            $table->string('module', 100)->nullable();   // Which module generated this
            $table->string('url', 500)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('route_name', 200)->nullable();

            // Related documents (for traceability chains)
            $table->string('related_type', 150)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            // Severity / classification
            $table->string('severity', 20)->default('info'); // info | warning | critical
            $table->boolean('is_sensitive')->default(false); // Flagged for enhanced retention
            $table->boolean('is_compliance_relevant')->default(false); // Pharma/SOX/GxP

            $table->timestamp('occurred_at');

            // Immutability: never allow updates on audit records
            $table->timestamps();

            $table->index(['tenant_id', 'auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'event', 'occurred_at']);
            $table->index(['tenant_id', 'user_id', 'occurred_at']);
            $table->index('occurred_at');
        });

        // ── Inventory Audit Ledger ───────────────────────────────────────────
        // Every stock quantity change is recorded here — full double-entry ledger.
        // This is the single source of truth for all inventory movements.
        Schema::create('inventory_audit_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('uom_id');

            // Transaction type
            $table->string('transaction_type', 50);
            // receipt | delivery | return | adjustment | transfer_in | transfer_out
            // | scrap | revaluation | opening | cycle_count_adj | production_in | production_out

            // Double-entry: from → to
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();

            // Quantity (always positive; direction determined by transaction_type)
            $table->decimal('qty', 19, 6);
            $table->decimal('qty_before', 19, 6);   // Running balance before
            $table->decimal('qty_after', 19, 6);    // Running balance after

            // Valuation
            $table->decimal('unit_cost', 19, 6)->default(0);
            $table->decimal('total_cost', 19, 6)->default(0);
            $table->decimal('value_before', 19, 6)->default(0);
            $table->decimal('value_after', 19, 6)->default(0);
            $table->string('costing_method', 30)->nullable();

            // Source reference (polymorphic)
            $table->string('reference_type', 150);
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number', 150)->nullable(); // Human-readable ref

            // Related party
            $table->unsignedBigInteger('partner_id')->nullable();  // Supplier/Customer
            $table->string('partner_type', 30)->nullable();        // supplier | customer

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 20, 8)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 150)->nullable();  // Snapshot
            $table->timestamp('transaction_date');                // Effective date
            $table->timestamps();

            // This table is append-only — no updates, no deletes
            $table->index(['tenant_id', 'product_id', 'warehouse_id', 'transaction_date']);
            $table->index(['tenant_id', 'transaction_type', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['lot_id']);
            $table->index(['serial_number_id']);
        });

        // ── Approval Logs ────────────────────────────────────────────────────
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();

            $table->string('approvable_type', 150);
            $table->unsignedBigInteger('approvable_id');
            $table->string('approvable_ref', 100)->nullable();

            $table->string('event', 30);
            // submitted | approved | rejected | returned | cancelled | auto_approved | escalated

            $table->unsignedBigInteger('actor_user_id');
            $table->string('actor_name', 150)->nullable();  // Snapshot
            $table->string('actor_role', 100)->nullable();  // Snapshot

            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();

            $table->text('comments')->nullable();
            $table->json('condition_snapshot')->nullable();  // Values at approval time
            $table->string('ip_address', 50)->nullable();

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['tenant_id', 'occurred_at']);
        });

        // ── Configuration Change Log ─────────────────────────────────────────
        Schema::create('config_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('config_area', 100);
            // inventory_settings | allocation_settings | costing_method | putaway_rules
            // | warehouse_settings | product_settings | uom_settings | etc.

            $table->string('config_key', 200);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('data_type', 30)->nullable(); // string|integer|boolean|json|decimal

            $table->unsignedBigInteger('changed_by');
            $table->string('changed_by_name', 150)->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'config_area', 'changed_at']);
        });

        // ── Data Access Log (sensitive items: pharma, controlled substances) ─
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name', 150)->nullable();
            $table->string('access_type', 30);
            // view | export | print | api_read
            $table->string('resource_type', 150);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('resource_ref', 150)->nullable();
            $table->string('purpose', 255)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();
            $table->index(['tenant_id', 'user_id', 'accessed_at']);
        });

        // ── Notification/Alert Rules (for stock alerts, expiry, thresholds) ──
        Schema::create('inventory_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->string('alert_type', 50);
            // low_stock | out_of_stock | overstock | expiry_soon | expiry_reached
            // | negative_stock | variance_threshold | pending_recount | recall_detected
            // | quality_fail | reorder_point | slow_moving | zero_movement

            $table->string('alert_name', 150);
            $table->text('description')->nullable();

            // Scope
            $table->unsignedBigInteger('product_id')->nullable();    // null = all products
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();

            // Thresholds (contextual per alert_type)
            $table->decimal('threshold_qty', 19, 6)->nullable();
            $table->decimal('threshold_value', 19, 6)->nullable();
            $table->integer('threshold_days')->nullable();
            $table->decimal('threshold_pct', 8, 4)->nullable();

            // Notification
            $table->json('notify_user_ids')->nullable();
            $table->json('notify_roles')->nullable();
            $table->json('notification_channels')->nullable();
            // ["email","slack","sms","in_app","webhook"]

            $table->string('frequency', 30)->default('once');
            // once | daily | per_occurrence | hourly

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ── Alert Notifications (triggered alert history) ────────────────────
        Schema::create('inventory_alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->string('tenant_id')->nullable()->index();
            $table->string('alert_type', 50);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->string('severity', 20)->default('warning'); // info|warning|critical
            $table->string('message', 500);
            $table->json('context_data')->nullable();
            $table->string('status', 20)->default('sent'); // sent|read|dismissed|actioned
            $table->timestamp('triggered_at');
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'alert_type', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_alert_notifications');
        Schema::dropIfExists('inventory_alert_rules');
        Schema::dropIfExists('data_access_logs');
        Schema::dropIfExists('config_change_logs');
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('inventory_audit_ledger');
        Schema::dropIfExists('audit_trails');
    }
};
