<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Valuation Module — Inventory Valuation & Costing Engine.
 *
 * Supported methods (user-configurable per warehouse/product/org):
 *   FIFO   — First In, First Out (layers consumed in receipt order)
 *   LIFO   — Last In, First Out (layers consumed reverse receipt order)
 *   AVCO   — Average Cost (weighted average recalculated on each receipt)
 *   Standard Cost — Fixed cost per period; variances tracked
 *   Specific Identification — Serial/lot-level exact cost
 *   FEFO   — First Expiry First Out (FEFO for perishables)
 *
 * Period closing: lock accounting periods, run revaluation.
 * Supports multi-currency valuation and landed cost distribution.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Costing Method Assignments ──────────────────────────────────────
        // Defines which costing method applies to each product/warehouse combo.
        // Hierarchy: product-level > category-level > warehouse-level > org-level > global
        Schema::create('costing_method_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();

            // Scope (most specific wins)
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();

            $table->string('costing_method', 30);
            // fifo | lifo | avco | standard | specific_identification | fefo

            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(true);

            // For standard costing
            $table->decimal('standard_cost', 19, 6)->nullable();
            $table->unsignedBigInteger('standard_cost_currency_id')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'warehouse_id', 'is_current']);
        });

        // ── AVCO Cost History ────────────────────────────────────────────────
        // Snapshots the running average cost after every movement.
        // Enables full cost trace for AVCO items.
        Schema::create('avco_cost_history', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('uom_id');

            // Transaction that caused this AVCO recalculation
            $table->string('trigger_type', 100); // receipt | return | adjustment | revaluation
            $table->unsignedBigInteger('trigger_id')->nullable();

            // Before
            $table->decimal('qty_before', 19, 6)->default(0);
            $table->decimal('avco_before', 19, 6)->default(0);
            $table->decimal('total_value_before', 19, 6)->default(0);

            // Transaction
            $table->decimal('transaction_qty', 19, 6);    // + for in, - for out
            $table->decimal('transaction_cost', 19, 6);   // Cost of the transaction

            // After recalculation
            $table->decimal('qty_after', 19, 6);
            $table->decimal('avco_after', 19, 6);
            $table->decimal('total_value_after', 19, 6);

            $table->decimal('avco_variance', 19, 6)->default(0); // Rounding difference

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'occurred_at']);
        });

        // ── Standard Cost Variances ──────────────────────────────────────────
        // Track purchase price variance (PPV) and usage variance for standard cost items.
        Schema::create('standard_cost_variances', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');

            $table->string('variance_type', 50);
            // purchase_price | exchange_rate | efficiency | usage | overhead | yield

            $table->string('source_type', 100); // PurchaseReceipt | StockMovement | etc.
            $table->unsignedBigInteger('source_id');

            $table->decimal('standard_cost', 19, 6);
            $table->decimal('actual_cost', 19, 6);
            $table->decimal('quantity', 19, 6);
            $table->decimal('variance_per_unit', 19, 6);
            $table->decimal('total_variance', 19, 6);

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('variance_account_id')->nullable(); // GL account
            $table->boolean('is_posted')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
        });

        // ── Inventory Revaluation ────────────────────────────────────────────
        // Manual or system-triggered revaluation of inventory costs.
        Schema::create('inventory_revaluations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();  // null = all warehouses

            $table->string('reference_number', 100);
            $table->string('revaluation_type', 30)->default('standard_cost_change');
            // standard_cost_change | currency_revaluation | market_adjustment
            // | write_down | write_up | landed_cost | opening_balance

            $table->string('status', 30)->default('draft');
            // draft | validated | posted | reversed

            $table->timestamp('revaluation_date');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->decimal('total_value_before', 19, 6)->default(0);
            $table->decimal('total_value_after', 19, 6)->default(0);
            $table->decimal('total_adjustment', 19, 6)->default(0);

            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('reversal_of_id')->nullable(); // Reversal reference

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_number']);
        });

        // ── Revaluation Lines ────────────────────────────────────────────────
        Schema::create('inventory_revaluation_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revaluation_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('valuation_layer_id')->nullable();

            $table->decimal('qty_on_hand', 19, 6);
            $table->decimal('cost_before', 19, 6);
            $table->decimal('cost_after', 19, 6);
            $table->decimal('cost_difference', 19, 6); // cost_after - cost_before
            $table->decimal('value_before', 19, 6);
            $table->decimal('value_after', 19, 6);
            $table->decimal('value_adjustment', 19, 6);

            $table->unsignedBigInteger('debit_account_id')->nullable();
            $table->unsignedBigInteger('credit_account_id')->nullable();

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('revaluation_id')->references('id')->on('inventory_revaluations')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });

        // ── Accounting Period Controls ────────────────────────────────────────
        // Lock accounting periods to prevent backdated inventory postings.
        Schema::create('inventory_accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->string('period_name', 100);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('fiscal_year')->nullable();
            $table->integer('period_number')->nullable(); // 1-12 for monthly

            $table->string('status', 30)->default('open');
            // open | soft_closed | hard_closed | locked

            // Period-end snapshot (closing inventory value)
            $table->decimal('closing_stock_value', 19, 6)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->integer('closing_item_count')->nullable();

            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_id', 'period_start', 'period_end']);
            $table->index(['tenant_id', 'status']);
        });

        // ── COGS (Cost of Goods Sold) Tracking ───────────────────────────────
        // Every delivery/issue creates a COGS record for profitability analysis.
        Schema::create('cogs_records', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');

            // Source (what caused the COGS)
            $table->string('source_type', 100); // DeliveryOrderLine | ScrapLine | ReturnLine
            $table->unsignedBigInteger('source_id');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('sales_order_id')->nullable();

            $table->decimal('qty', 19, 6);
            $table->unsignedBigInteger('uom_id');
            $table->string('costing_method', 30);
            $table->decimal('unit_cost', 19, 6);
            $table->decimal('total_cogs', 19, 6);
            $table->decimal('sales_price', 19, 6)->nullable();
            $table->decimal('gross_profit', 19, 6)->nullable();
            $table->decimal('gross_margin_pct', 8, 4)->nullable();

            $table->unsignedBigInteger('valuation_layer_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('cogs_account_id')->nullable();
            $table->boolean('is_posted')->default(false);
            $table->unsignedBigInteger('journal_entry_id')->nullable();

            $table->timestamp('transaction_date');
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['tenant_id', 'product_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cogs_records');
        Schema::dropIfExists('inventory_accounting_periods');
        Schema::dropIfExists('inventory_revaluation_lines');
        Schema::dropIfExists('inventory_revaluations');
        Schema::dropIfExists('standard_cost_variances');
        Schema::dropIfExists('avco_cost_history');
        Schema::dropIfExists('costing_method_assignments');
    }
};
