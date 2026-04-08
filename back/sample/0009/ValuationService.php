<?php

namespace App\Services\Inventory;

use App\Models\{CostingLayer, StockLedgerEntry, StockPosition};
use Illuminate\Support\Facades\DB;

/**
 * ValuationService
 *
 * Handles all inventory costing calculations:
 *
 *  FIFO  — layers consumed oldest-first
 *  LIFO  — layers consumed newest-first
 *  AVCO  — running weighted average recalculated on each receipt
 *  FEFO  — layers consumed by earliest expiry date
 *  FMFO  — layers consumed by earliest manufacture date
 *  Specific ID — layer selected by explicit lot/batch/serial
 *  Standard Cost — fixed cost, variances captured separately
 *  Retail Method — cost estimated by cost ratio to retail price
 */
class ValuationService
{
    // ── Create a new costing layer (on every receipt/production in) ──────────
    public function createLayer(StockLedgerEntry $entry, array $params, string $method): CostingLayer
    {
        return CostingLayer::create([
            'organization_id'  => $params['organization_id'],
            'product_id'       => $params['product_id'],
            'product_variant_id' => $params['product_variant_id'] ?? null,
            'warehouse_id'     => $params['warehouse_id'],
            'lot_id'           => $params['lot_id'] ?? null,
            'batch_id'         => $params['batch_id'] ?? null,
            'valuation_method' => $method,
            'layer_reference'  => $entry->reference_number,
            'initial_quantity' => $params['quantity'],
            'remaining_quantity'=> $params['quantity'],
            'unit_cost'        => $params['unit_cost'],
            'total_cost'       => $params['quantity'] * $params['unit_cost'],
            'manufacture_date' => $params['manufacture_date'] ?? null,
            'expiry_date'      => $params['expiry_date'] ?? null,
            'received_at'      => now(),
        ]);
    }

    // ── Resolve cost for an issue based on the valuation method ─────────────
    public function resolveIssueCost(
        int     $productId,
        ?int    $variantId,
        int     $warehouseId,
        float   $quantity,
        string  $method,
        ?int    $lotId   = null,
        ?int    $batchId = null,
    ): array {
        return match($method) {
            'FIFO'        => $this->fifo($productId, $variantId, $warehouseId, $quantity),
            'LIFO'        => $this->lifo($productId, $variantId, $warehouseId, $quantity),
            'FEFO'        => $this->fefo($productId, $variantId, $warehouseId, $quantity),
            'FMFO'        => $this->fmfo($productId, $variantId, $warehouseId, $quantity),
            'AVCO'        => $this->avco($productId, $variantId, $warehouseId, $quantity),
            'specific_id' => $this->specificId($productId, $variantId, $warehouseId, $quantity, $lotId, $batchId),
            'standard'    => $this->standardCost($productId, $variantId, $quantity),
            'retail'      => $this->retailMethod($productId, $variantId, $warehouseId, $quantity),
            default       => $this->avco($productId, $variantId, $warehouseId, $quantity),
        };
    }

    // ── FIFO: consume oldest layers first ────────────────────────────────────
    private function fifo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayers(
            $productId, $variantId, $warehouseId, $quantity,
            orderBy: ['received_at' => 'asc'],
        );
    }

    // ── LIFO: consume newest layers first ────────────────────────────────────
    private function lifo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayers(
            $productId, $variantId, $warehouseId, $quantity,
            orderBy: ['received_at' => 'desc'],
        );
    }

    // ── FEFO: consume layers with nearest expiry first ───────────────────────
    private function fefo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayers(
            $productId, $variantId, $warehouseId, $quantity,
            orderBy: ['expiry_date' => 'asc', 'received_at' => 'asc'],
        );
    }

    // ── FMFO: consume layers with oldest manufacture date first ─────────────
    private function fmfo(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        return $this->consumeLayers(
            $productId, $variantId, $warehouseId, $quantity,
            orderBy: ['manufacture_date' => 'asc', 'received_at' => 'asc'],
        );
    }

    // ── AVCO: return current weighted average cost ───────────────────────────
    private function avco(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        $avgCost = StockPosition::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->avg('average_cost') ?? 0;

        return [
            'unit_cost'       => round($avgCost, 4),
            'total_cost'      => round($avgCost * $quantity, 4),
            'consumed_layers' => [], // AVCO doesn't consume layers
        ];
    }

    // ── Specific Identification: use lot/batch's own cost ───────────────────
    private function specificId(int $productId, ?int $variantId, int $warehouseId, float $quantity, ?int $lotId, ?int $batchId): array
    {
        if ($lotId) {
            $cost = \App\Models\Lot::findOrFail($lotId)->unit_cost ?? 0;
        } elseif ($batchId) {
            $cost = \App\Models\Batch::findOrFail($batchId)->unit_cost ?? 0;
        } else {
            throw new \InvalidArgumentException('Specific Identification requires a lot_id or batch_id');
        }

        // Find the matching layer
        $layer = CostingLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($lotId, fn ($q) => $q->where('lot_id', $lotId))
            ->when($batchId, fn ($q) => $q->where('batch_id', $batchId))
            ->where('is_fully_consumed', false)
            ->first();

        return [
            'unit_cost'  => $cost,
            'total_cost' => round($cost * $quantity, 4),
            'consumed_layers' => $layer ? [[
                'layer_id' => $layer->id,
                'quantity' => $quantity,
                'unit_cost'=> $cost,
                'total_cost' => $cost * $quantity,
            ]] : [],
        ];
    }

    // ── Standard Cost: use product's preset standard cost ───────────────────
    private function standardCost(int $productId, ?int $variantId, float $quantity): array
    {
        $standardCost = \App\Models\Product::find($productId)?->standard_cost ?? 0;
        if ($variantId) {
            $standardCost = \App\Models\ProductVariant::find($variantId)?->standard_cost ?? $standardCost;
        }

        return [
            'unit_cost'       => $standardCost,
            'total_cost'      => round($standardCost * $quantity, 4),
            'consumed_layers' => [],
        ];
    }

    // ── Retail Method: cost ratio applied to retail price ───────────────────
    private function retailMethod(int $productId, ?int $variantId, int $warehouseId, float $quantity): array
    {
        // Cost ratio = Total Cost Value / Total Retail Value in warehouse
        $positions = StockPosition::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->get();

        $totalCost   = $positions->sum('total_cost_value');
        $totalRetail = $positions->sum(fn ($p) => $p->qty_on_hand * ($p->product?->standard_price ?? 0));

        $costRatio = $totalRetail > 0 ? ($totalCost / $totalRetail) : 0;
        $retailPrice = \App\Models\Product::find($productId)?->standard_price ?? 0;
        $unitCost = $retailPrice * $costRatio;

        return [
            'unit_cost'       => round($unitCost, 4),
            'total_cost'      => round($unitCost * $quantity, 4),
            'consumed_layers' => [],
        ];
    }

    // ── Recalculate AVCO after a receipt ─────────────────────────────────────
    public function recalculateAvco(StockPosition $position, float $quantity, float $unitCost): void
    {
        $currentQty  = $position->qty_on_hand;
        $currentCost = $position->average_cost;

        if ($quantity > 0) {
            // Receipt: new AVCO = (existing value + new value) / new total qty
            $newQty  = $currentQty + $quantity;
            $newAvco = $newQty > 0
                ? (($currentQty * $currentCost) + ($quantity * $unitCost)) / $newQty
                : 0;
        } else {
            // Issue: AVCO stays the same (no change on outflow)
            $newAvco = $currentCost;
            $newQty  = $currentQty; // already decremented by upsertPosition
        }

        $position->update([
            'average_cost'    => round($newAvco, 6),
            'total_cost_value'=> round($newQty * $newAvco, 4),
        ]);
    }

    // ── Generic layer consumer (used by FIFO/LIFO/FEFO/FMFO) ────────────────
    private function consumeLayers(int $productId, ?int $variantId, int $warehouseId, float $quantity, array $orderBy): array
    {
        $query = CostingLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_fully_consumed', false)
            ->where('remaining_quantity', '>', 0);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $layers   = $query->get();
        $remaining = $quantity;
        $consumed  = [];
        $totalCost = 0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $take = min($remaining, $layer->remaining_quantity);

            $consumed[] = [
                'layer_id'   => $layer->id,
                'quantity'   => $take,
                'unit_cost'  => $layer->unit_cost,
                'total_cost' => round($take * $layer->unit_cost, 4),
            ];

            $totalCost += $take * $layer->unit_cost;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            // Layer shortage — use last known cost for the remainder
            $lastCost = $layers->last()?->unit_cost ?? 0;
            $totalCost += $remaining * $lastCost;
        }

        $unitCost = $quantity > 0 ? ($totalCost / $quantity) : 0;

        return [
            'unit_cost'       => round($unitCost, 6),
            'total_cost'      => round($totalCost, 4),
            'consumed_layers' => $consumed,
        ];
    }
}
