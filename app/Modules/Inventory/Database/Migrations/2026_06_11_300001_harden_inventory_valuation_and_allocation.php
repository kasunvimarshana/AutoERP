<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_categories', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('description');
        });

        Schema::table('inventory_allocations', function (Blueprint $table): void {
            $table->string('allocation_method', 30)->default('manual')->after('allocation_date');
            $table->decimal('quantity_reversed', 20, 6)->default('0.000000')->after('quantity_issued');
        });

        Schema::create('inventory_allocation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('allocation_id')->constrained('inventory_allocations')->cascadeOnDelete();
            $table->foreignId('stock_balance_id')->constrained('inventory_stock_balances')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->nullOnDelete();
            $table->decimal('quantity_allocated', 20, 6);
            $table->decimal('quantity_issued', 20, 6)->default('0.000000');
            $table->decimal('quantity_reversed', 20, 6)->default('0.000000');
            $table->decimal('quantity_released', 20, 6)->default('0.000000');
            $table->decimal('quantity_remaining', 20, 6);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_allocation_lines_scope_idx');
            $table->index('allocation_id', 'inventory_allocation_lines_allocation_idx');
            $table->index('stock_balance_id', 'inventory_allocation_lines_balance_idx');
            $table->index('batch_id', 'inventory_allocation_lines_batch_idx');
            $table->index('serial_number_id', 'inventory_allocation_lines_serial_idx');
        });

        Schema::create('inventory_allocation_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('allocation_id')->constrained('inventory_allocations')->cascadeOnDelete();
            $table->foreignId('allocation_line_id')->constrained('inventory_allocation_lines')->cascadeOnDelete();
            $table->foreignId('movement_id')->constrained('inventory_movements')->restrictOnDelete();
            $table->decimal('quantity_issued', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->foreignId('reversal_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique('movement_id', 'inventory_allocation_issues_movement_uk');
            $table->index('allocation_id', 'inventory_allocation_issues_allocation_idx');
            $table->index('allocation_line_id', 'inventory_allocation_issues_line_idx');
            $table->index('reversal_movement_id', 'inventory_allocation_issues_reversal_idx');
        });

        Schema::create('inventory_valuation_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('issue_movement_id')->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('valuation_layer_id')->constrained('inventory_valuation_layers')->restrictOnDelete();
            $table->decimal('quantity_consumed', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->foreignId('reversed_by_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['issue_movement_id', 'valuation_layer_id'],
                'inventory_valuation_consumptions_issue_layer_uk',
            );
            $table->index('valuation_layer_id', 'inventory_valuation_consumptions_layer_idx');
            $table->index('reversed_by_movement_id', 'inventory_valuation_consumptions_reversal_idx');
        });

        DB::table('inventory_allocations')
            ->orderBy('id')
            ->chunkById(200, function ($allocations): void {
                foreach ($allocations as $allocation) {
                    $balance = DB::table('inventory_stock_balances')
                        ->where('tenant_id', $allocation->tenant_id)
                        ->where('organization_unit_id', $allocation->organization_unit_id)
                        ->where('item_id', $allocation->item_id)
                        ->where('item_variant_id', $allocation->item_variant_id)
                        ->where('warehouse_id', $allocation->warehouse_id)
                        ->where('warehouse_location_id', $allocation->warehouse_location_id)
                        ->where('batch_id', $allocation->batch_id)
                        ->first();
                    if ($balance === null) {
                        if (preg_match('/^-?0+(?:\.0+)?$/', (string) $allocation->quantity_remaining) !== 1) {
                            throw new RuntimeException(
                                "Active inventory allocation [{$allocation->id}] has no matching stock balance.",
                            );
                        }

                        continue;
                    }

                    DB::table('inventory_allocation_lines')->insert([
                        'tenant_id' => $allocation->tenant_id,
                        'organization_unit_id' => $allocation->organization_unit_id,
                        'allocation_id' => $allocation->id,
                        'stock_balance_id' => $balance->id,
                        'batch_id' => $allocation->batch_id,
                        'serial_number_id' => $allocation->serial_number_id,
                        'quantity_allocated' => $allocation->quantity_allocated,
                        'quantity_issued' => $allocation->quantity_issued,
                        'quantity_reversed' => '0.000000',
                        'quantity_released' => $allocation->quantity_released,
                        'quantity_remaining' => $allocation->quantity_remaining,
                        'created_at' => $allocation->created_at,
                        'updated_at' => $allocation->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_consumptions');
        Schema::dropIfExists('inventory_allocation_issues');
        Schema::dropIfExists('inventory_allocation_lines');

        Schema::table('inventory_allocations', function (Blueprint $table): void {
            $table->dropColumn(['allocation_method', 'quantity_reversed']);
        });

        Schema::table('item_categories', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
