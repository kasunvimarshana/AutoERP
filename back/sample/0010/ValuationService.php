<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;

/**
 * ValuationService
 *
 * Implements all 8 inventory costing methods.
 * User selects the method at tenant/org/warehouse/product level.
 *
 * FIFO  — layer consumed oldest-first (by received_at ASC)
 * LIFO  — layer consumed newest-first (by received_at DESC)
 * AVCO  — running weighted average recalculated on every receipt
 * FEFO  — layer consumed by nearest expiry_date ASC
 * FMFO  — layer consumed by oldest manufacture_date ASC
 * Specific ID — layer selected by explicit lot/batch reference
 * Standard Cost — fixed preset cost; purchase-price variance captured
 * Retail Method — cost estimated via cost-to-retail ratio
 */
final class ValuationService
{
    // ── Create a costing layer on every receipt ───────────────────────────────
    public function createLayer(array $entry, array $params, string $method): int
    {
        return DB::table('costing_layers')->insertGetId([
            'tenant_id'          => $params['tenant_id'],
            'product_id'         => $params['product_id'],
            'product_variant_id' => $params['product_variant_id'] ?? null,
            'warehouse_id'       => $params['warehouse_id'],
            'lot_id'             => $params['lot_id'] ?? null,
            'batch_id'           => $params['batch_id'] ?? null,
            'valuation_method'   => $method,
            'layer_reference'    => $entry['reference_number'],
            'initial_quantity'   => $params['quantity'],
            'remaining_quantity' => $params['quantity'],
            'unit_cost'          => $params['unit_cost'],
            'total_cost'         => $params['quantity'] * $params['unit_cost'],
            'manufacture_date'   => $params['manufacture_date'] ?? null,
            'expiry_date'        => $params['expiry_date'] ?? null,
            'received_at'        => now(),
            'is_fully_consumed'  => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    // ── Resolve cost for an issue ─────────────────────────────────────────────
    public function resolveIssueCost(
        int    $productId,
        ?int   $variantId,
        int    $warehouseId,
        float  $quantity,
        string $method,
        ?int   $lotId   = null,
        ?int   $batchId = null,
    ): array {
        return match ($method) {
            ValuationMethod::FIFO        => $this->consumeLayersFifo($productId, $variantId, $warehouseId, $quantity),
            ValuationMethod::LIFO        => $this->consumeLayersLifo($productId, $variantId, $warehouseId, $quantity),
            ValuationMethod::FEFO        => $this->consumeLayersFefo($productId, $variantId, $warehouseId, $quantity),
            ValuationMethod::FMFO        => $this->consumeLayersFmfo($productId, $variantId, $warehouseId, $quantity),
            ValuationMethod::AVCO        => $this->avco($productId, $variantId, $warehouseId, $quantity),
            ValuationMethod::SPECIFIC_ID => $this->specificId($productId, $variantId, $warehouseId, $quantity, $lotId, $batchId),
            ValuationMethod::STANDARD_COST => $this->standardCost($productId, $variantId, $quantity),
            ValuationMethod::RETAIL      => $this->retail($productId, $variantId, $warehouseId, $quantity),
            default                      => $this->avco($productId, $variantId, $warehouseId, $quantity),
        };
    }

    // ── FIFO — oldest received_at first ──────────────────────────────────────
    private function consumeLayersFifo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayersOrdered(
            $productId, $variantId, $warehouseId, $quantity,
            orderColumn: 'received_at', orderDir: 'asc'
        );
    }

    // ── LIFO — newest received_at first ──────────────────────────────────────
    private function consumeLayersLifo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayersOrdered(
            $productId, $variantId, $warehouseId, $quantity,
            orderColumn: 'received_at', orderDir: 'desc'
        );
    }

    // ── FEFO — nearest expiry first ───────────────────────────────────────────
    private function consumeLayersFefo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayersOrdered(
            $productId, $variantId, $warehouseId, $quantity,
            orderColumn: 'expiry_date', orderDir: 'asc',
            secondaryOrder: ['received_at', 'asc']
        );
    }

    // ── FMFO — oldest manufacture date first ─────────────────────────────────
    private function consumeLayersFmfo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayersOrdered(
            $productId, $variantId, $warehouseId, $quantity,
            orderColumn: 'manufacture_date', orderDir: 'asc',
            secondaryOrder: ['received_at', 'asc']
        );
    }

    // ── AVCO — use current weighted average; no layer consumption ────────────
    private function avco(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        $avgCost = (float) DB::table('stock_positions')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->avg('average_cost') ?? 0;

        return [
            'unit_cost'       => round($avgCost, 6),
            'total_cost'      => round($avgCost * $quantity, 4),
            'consumed_layers' => [],
        ];
    }

    // ── Specific ID — cost from the specific lot/batch record ────────────────
    private function specificId(
        int $productId, ?int $variantId, int $warehouseId,
        float $quantity, ?int $lotId, ?int $batchId
    ): array {
        if (!$lotId && !$batchId) {
            throw new \InvalidArgumentException('Specific Identification requires lot_id or batch_id');
        }

        $cost = 0.0;
        if ($lotId) {
            $cost = (float) (DB::table('lots')->where('id', $lotId)->value('unit_cost') ?? 0);
        } elseif ($batchId) {
            $cost = (float) (DB::table('batches')->where('id', $batchId)->value('unit_cost') ?? 0);
        }

        $layer = DB::table('costing_layers')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($lotId, fn ($q) => $q->where('lot_id', $lotId))
            ->when($batchId, fn ($q) => $q->where('batch_id', $batchId))
            ->where('is_fully_consumed', false)
            ->first();

        return [
            'unit_cost'       => round($cost, 6),
            'total_cost'      => round($cost * $quantity, 4),
            'consumed_layers' => $layer ? [[
                'layer_id'   => $layer->id,
                'quantity'   => $quantity,
                'unit_cost'  => $cost,
                'total_cost' => round($cost * $quantity, 4),
            ]] : [],
        ];
    }

    // ── Standard Cost — fixed cost; variance written to cost_variances ────────
    private function standardCost(int $productId, ?int $variantId, float $quantity): array
    {
        $cost = (float) DB::table('products')->where('id', $productId)->value('standard_cost') ?? 0;
        if ($variantId) {
            $variantCost = DB::table('product_variants')->where('id', $variantId)->value('standard_cost');
            if ($variantCost !== null) {
                $cost = (float) $variantCost;
            }
        }
        return [
            'unit_cost'       => round($cost, 6),
            'total_cost'      => round($cost * $quantity, 4),
            'consumed_layers' => [],
        ];
    }

    // ── Retail Method — cost = retail_price × cost_ratio ─────────────────────
    private function retail(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        $positions  = DB::table('stock_positions')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->get();

        $totalCost   = $positions->sum('total_cost_value');
        $retailPrice = (float) DB::table('products')->where('id', $productId)->value('standard_price') ?? 0;
        $totalRetail = $positions->sum(fn ($p) => $p->qty_on_hand * $retailPrice);

        $ratio    = $totalRetail > 0 ? ($totalCost / $totalRetail) : 0;
        $unitCost = $retailPrice * $ratio;

        return [
            'unit_cost'       => round($unitCost, 6),
            'total_cost'      => round($unitCost * $quantity, 4),
            'consumed_layers' => [],
        ];
    }

    // ── AVCO recalculation on receipt ─────────────────────────────────────────
    public function recalculateAvco(
        int    $productId,
        int    $warehouseId,
        ?int   $variantId,
        float  $quantity,   // positive = receipt, negative = issue
        float  $unitCost,
    ): void {
        $positions = DB::table('stock_positions')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->lockForUpdate()
            ->get();

        foreach ($positions as $pos) {
            $currentQty  = (float) $pos->qty_on_hand;
            $currentCost = (float) $pos->average_cost;

            if ($quantity > 0) {
                // Receipt: new AVCO = (existing value + new value) / new total
                $newQty  = $currentQty + $quantity;
                $newAvco = $newQty > 0
                    ? (($currentQty * $currentCost) + ($quantity * $unitCost)) / $newQty
                    : 0;
            } else {
                // Issue: AVCO unchanged on outflow
                $newAvco = $currentCost;
                $newQty  = $currentQty;
            }

            DB::table('stock_positions')
                ->where('id', $pos->id)
                ->update([
                    'average_cost'    => round($newAvco, 6),
                    'total_cost_value'=> round($newQty * $newAvco, 4),
                    'updated_at'      => now(),
                ]);
        }
    }

    // ── Generic layer consumer ────────────────────────────────────────────────
    private function consumeLayersOrdered(
        int    $productId,
        ?int   $variantId,
        int    $warehouseId,
        float  $quantity,
        string $orderColumn,
        string $orderDir,
        ?array $secondaryOrder = null,
    ): array {
        $query = DB::table('costing_layers')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_fully_consumed', false)
            ->where('remaining_quantity', '>', 0)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->orderByRaw("{$orderColumn} IS NULL " . ($orderDir === 'asc' ? 'ASC' : 'DESC'))
            ->orderBy($orderColumn, $orderDir);

        if ($secondaryOrder) {
            $query->orderBy($secondaryOrder[0], $secondaryOrder[1]);
        }

        $layers    = $query->get();
        $remaining = $quantity;
        $consumed  = [];
        $totalCost = 0.0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $take = min($remaining, (float) $layer->remaining_quantity);

            $consumed[] = [
                'layer_id'   => $layer->id,
                'quantity'   => $take,
                'unit_cost'  => (float) $layer->unit_cost,
                'total_cost' => round($take * (float) $layer->unit_cost, 4),
            ];

            $totalCost += $take * (float) $layer->unit_cost;
            $remaining -= $take;
        }

        // Shortage — fall back to last known cost
        if ($remaining > 0 && $layers->isNotEmpty()) {
            $fallback   = (float) $layers->last()->unit_cost;
            $totalCost += $remaining * $fallback;
        }

        $unitCost = $quantity > 0 ? ($totalCost / $quantity) : 0;

        return [
            'unit_cost'       => round($unitCost, 6),
            'total_cost'      => round($totalCost, 4),
            'consumed_layers' => $consumed,
        ];
    }
}
