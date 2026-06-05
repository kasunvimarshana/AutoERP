<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class InventoryValuationService
{
    public function __construct(private readonly InventoryServiceSupport $support) {}

    /**
     * @param  array<string, mixed>  $movement
     * @param  array<string, mixed>  $line
     */
    public function createCostLayerForReceipt(array $movement, array $line): ?int
    {
        if (! isset($line['unit_cost'])) {
            return null;
        }

        return DB::table('inventory_cost_layers')->insertGetId([
            'tenant_id' => (int) $movement['tenant_id'],
            'organization_unit_id' => $movement['organization_unit_id'] ?? null,
            'item_id' => (int) $line['item_id'],
            'variant_id' => $line['variant_id'] ?? null,
            'batch_id' => $line['batch_id'] ?? null,
            'serial_id' => $line['serial_id'] ?? null,
            'warehouse_id' => $line['warehouse_id'] ?? null,
            'location_id' => $line['location_id'] ?? null,
            'valuation_method' => $line['valuation_method'] ?? 'fifo',
            'layer_date' => now()->toDateString(),
            'quantity_in' => (float) $movement['base_quantity'],
            'quantity_remaining' => (float) $movement['base_quantity'],
            'unit_cost' => (float) $line['unit_cost'],
            'reference_type' => $movement['source_type'] ?? null,
            'reference_id' => $movement['source_id'] ?? null,
            'is_closed' => false,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{quantity_consumed: float, total_cost: float, unit_cost: float|null}
     */
    public function consumeCostLayersForIssue(array $criteria, float $baseQuantity): array
    {
        $remaining = $baseQuantity;
        $totalCost = 0.0;

        $layers = DB::table('inventory_cost_layers')
            ->where('tenant_id', (int) $criteria['tenant_id'])
            ->where('item_id', (int) $criteria['item_id'])
            ->where('quantity_remaining', '>', 0)
            ->where('is_closed', false)
            ->tap(fn ($query) => $this->support->whereNullable($query, 'variant_id', $criteria['variant_id'] ?? null))
            ->tap(fn ($query) => $this->support->whereNullable($query, 'batch_id', $criteria['batch_id'] ?? null))
            ->tap(fn ($query) => $this->support->whereNullable($query, 'serial_id', $criteria['serial_id'] ?? null))
            ->when(isset($criteria['warehouse_id']), fn ($query) => $query->where('warehouse_id', $criteria['warehouse_id']))
            ->when(array_key_exists('location_id', $criteria), fn ($query) => $this->support->whereNullable($query, 'location_id', $criteria['location_id']))
            ->orderBy('layer_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (float) $layer->quantity_remaining);
            $nextRemaining = (float) $layer->quantity_remaining - $take;
            $totalCost += $take * (float) $layer->unit_cost;
            $remaining -= $take;

            DB::table('inventory_cost_layers')
                ->where('id', (int) $layer->id)
                ->update([
                    'quantity_remaining' => $this->support->roundQuantity($nextRemaining),
                    'is_closed' => $nextRemaining <= 0.0001,
                    'row_version' => ((int) $layer->row_version) + 1,
                    'updated_at' => now(),
                ]);
        }

        $consumed = $baseQuantity - max(0, $remaining);

        return [
            'quantity_consumed' => $this->support->roundQuantity($consumed),
            'total_cost' => round($totalCost, 4),
            'unit_cost' => $consumed > 0 ? round($totalCost / $consumed, 4) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function calculateMovingAverage(array $criteria): ?float
    {
        $row = DB::table('inventory_cost_layers')
            ->selectRaw('sum(quantity_remaining * unit_cost) as value_sum, sum(quantity_remaining) as quantity_sum')
            ->where('tenant_id', (int) $criteria['tenant_id'])
            ->where('item_id', (int) $criteria['item_id'])
            ->where('quantity_remaining', '>', 0)
            ->where('is_closed', false)
            ->first();

        $quantity = (float) ($row->quantity_sum ?? 0);
        if ($quantity <= 0) {
            return null;
        }

        return round((float) $row->value_sum / $quantity, 4);
    }
}
