<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 0003 — Inventory Settings
 *
 * Per-tenant, per-organization overridable inventory configuration.
 * All methods are user-selectable at runtime — not compile-time.
 *
 * Valuation Methods:
 *   FIFO | LIFO | AVCO | FEFO | FMFO | specific_id | standard_cost | retail
 *
 * Inventory Management Methods:
 *   perpetual | periodic
 *
 * Stock Rotation Strategies:
 *   FIFO | LIFO | FEFO | FMFO | LEFO
 *
 * Allocation Algorithms:
 *   strict_reservation | soft_reservation | fair_share | priority_based
 *   wave_picking | zone_picking | batch_picking | cluster_picking
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            // null organization_id = tenant default; non-null = org override

            // ── Valuation ──────────────────────────────────────────────────
            $table->string('default_valuation_method', 30)->default('AVCO');
            $table->string('allowed_valuation_methods')->default('FIFO,LIFO,AVCO,FEFO,FMFO,specific_id,standard_cost,retail');

            // ── Management ─────────────────────────────────────────────────
            $table->string('inventory_management_method', 20)->default('perpetual');

            // ── Rotation ───────────────────────────────────────────────────
            $table->string('default_stock_rotation', 20)->default('FIFO');

            // ── Allocation ─────────────────────────────────────────────────
            $table->string('default_allocation_algorithm', 30)->default('soft_reservation');
            $table->integer('soft_reservation_ttl_minutes')->default(60);

            // ── Batch / Lot / Serial ───────────────────────────────────────
            $table->boolean('batch_tracking_enabled')->default(true);
            $table->boolean('lot_tracking_enabled')->default(true);
            $table->boolean('serial_tracking_enabled')->default(true);
            $table->boolean('expiry_tracking_enabled')->default(true);
            $table->integer('expiry_warning_days')->default(30);

            // ── Cost ───────────────────────────────────────────────────────
            $table->boolean('landed_cost_enabled')->default(false);
            $table->boolean('overhead_cost_enabled')->default(false);
            $table->decimal('default_overhead_rate', 8, 4)->default(0);

            // ── Negative Stock ─────────────────────────────────────────────
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('warn_on_negative_stock')->default(true);

            // ── Precision ──────────────────────────────────────────────────
            $table->integer('quantity_decimals')->default(4);
            $table->integer('price_decimals')->default(4);
            $table->integer('cost_decimals')->default(6);

            // ── Reorder ────────────────────────────────────────────────────
            $table->boolean('auto_reorder_enabled')->default(false);
            $table->integer('low_stock_threshold_days')->default(7);

            // ── ABC/XYZ Classification ─────────────────────────────────────
            $table->boolean('abc_classification_enabled')->default(false);
            $table->boolean('xyz_classification_enabled')->default(false);

            $table->json('custom_config')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_id'], 'inv_settings_unique');
        });

        // ── Per-product valuation overrides ─────────────────────────────────
        Schema::create('product_valuation_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->string('valuation_method', 30);
            $table->string('stock_rotation', 20)->nullable();
            $table->string('allocation_algorithm', 30)->nullable();
            $table->decimal('standard_cost', 20, 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_valuation_overrides');
        Schema::dropIfExists('inventory_settings');
    }
};
