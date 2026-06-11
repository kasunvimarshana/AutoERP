<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock_balances', function (Blueprint $table): void {
            $table->decimal('quantity_returned', 20, 6)->default('0.000000');
            $table->decimal('quantity_in_transit', 20, 6)->default('0.000000');
            $table->decimal('quantity_damaged', 20, 6)->default('0.000000');
            $table->decimal('quantity_quarantine', 20, 6)->default('0.000000');
            $table->decimal('quantity_expired', 20, 6)->default('0.000000');
            $table->decimal('quantity_scrapped', 20, 6)->default('0.000000');
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
        });

        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('released_at')->nullable();
        });

        Schema::table('inventory_allocations', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('released_at')->nullable();
        });

        Schema::table('inventory_transfers', function (Blueprint $table): void {
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        Schema::table('inventory_transfer_lines', function (Blueprint $table): void {
            $table->decimal('dispatched_quantity', 20, 6)->default('0.000000');
            $table->decimal('received_quantity', 20, 6)->default('0.000000');
            $table->decimal('cancelled_quantity', 20, 6)->default('0.000000');
            $table->foreignId('outbound_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->foreignId('inbound_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
        });

        Schema::create('inventory_stock_state_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('stock_balance_id')->nullable()->constrained('inventory_stock_balances')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->nullOnDelete();
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
            $table->decimal('quantity', 20, 6);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_state_changes_scope_idx');
            $table->index(['source_type', 'source_id'], 'inventory_state_changes_source_idx');
            $table->index(['item_id', 'warehouse_id'], 'inventory_state_changes_item_wh_idx');
            $table->index(['from_state', 'to_state'], 'inventory_state_changes_states_idx');
        });

        Schema::create('inventory_cost_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('adjustment_number', 80);
            $table->date('adjustment_date');
            $table->string('status', 30)->default('draft');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'adjustment_number'], 'inventory_cost_adjustments_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_cost_adjustments_scope_idx');
            $table->index('status', 'inventory_cost_adjustments_status_idx');
        });

        Schema::create('inventory_cost_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('inventory_cost_adjustment_id')->constrained('inventory_cost_adjustments')->cascadeOnDelete();
            $table->foreignId('valuation_layer_id')->constrained('inventory_valuation_layers')->restrictOnDelete();
            $table->decimal('adjustment_amount', 20, 6);
            $table->decimal('remaining_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost_before', 20, 6)->default('0.000000');
            $table->decimal('unit_cost_after', 20, 6)->default('0.000000');
            $table->decimal('remaining_value_before', 20, 6)->default('0.000000');
            $table->decimal('remaining_value_after', 20, 6)->default('0.000000');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('inventory_cost_adjustment_id', 'inventory_cost_adj_lines_header_idx');
            $table->index('valuation_layer_id', 'inventory_cost_adj_lines_layer_idx');
        });

        Schema::create('inventory_stock_counts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('count_number', 80);
            $table->date('count_date');
            $table->string('count_type', 30)->default('stock_count');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->foreignId('inventory_adjustment_id')->nullable()->constrained('inventory_adjustments')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'count_number'], 'inventory_stock_counts_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_stock_counts_scope_idx');
            $table->index(['warehouse_id', 'warehouse_location_id'], 'inventory_stock_counts_wh_idx');
            $table->index('status', 'inventory_stock_counts_status_idx');
        });

        Schema::create('inventory_stock_count_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('inventory_stock_count_id')->constrained('inventory_stock_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->nullOnDelete();
            $table->decimal('system_quantity', 20, 6)->default('0.000000');
            $table->decimal('counted_quantity', 20, 6)->default('0.000000');
            $table->decimal('variance_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->foreignId('inventory_adjustment_line_id')->nullable()->constrained('inventory_adjustment_lines')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('inventory_stock_count_id', 'inventory_stock_count_lines_count_idx');
            $table->index('item_id', 'inventory_stock_count_lines_item_idx');
            $table->index('batch_id', 'inventory_stock_count_lines_batch_idx');
            $table->index('serial_number_id', 'inventory_stock_count_lines_serial_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_count_lines');
        Schema::dropIfExists('inventory_stock_counts');
        Schema::dropIfExists('inventory_cost_adjustment_lines');
        Schema::dropIfExists('inventory_cost_adjustments');
        Schema::dropIfExists('inventory_stock_state_changes');

        Schema::table('inventory_transfer_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inbound_movement_id');
            $table->dropConstrainedForeignId('outbound_movement_id');
            $table->dropColumn(['dispatched_quantity', 'received_quantity', 'cancelled_quantity']);
        });

        Schema::table('inventory_transfers', function (Blueprint $table): void {
            $table->dropColumn([
                'dispatched_by',
                'dispatched_at',
                'received_by',
                'received_at',
                'reversed_by',
                'reversed_at',
                'cancelled_by',
                'cancelled_at',
            ]);
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropColumn(['from_state', 'to_state']);
        });

        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->dropColumn(['created_by', 'released_by', 'released_at']);
        });

        Schema::table('inventory_allocations', function (Blueprint $table): void {
            $table->dropColumn(['created_by', 'issued_by', 'issued_at', 'released_by', 'released_at']);
        });

        Schema::table('inventory_stock_balances', function (Blueprint $table): void {
            $table->dropColumn([
                'quantity_returned',
                'quantity_in_transit',
                'quantity_damaged',
                'quantity_quarantine',
                'quantity_expired',
                'quantity_scrapped',
            ]);
        });
    }
};
