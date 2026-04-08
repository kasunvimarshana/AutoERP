<?php

namespace App\Modules\Valuation\Services;

use App\Modules\Inventory\Models\InventoryValuationLayer;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Valuation\Models\AvgCostHistory;
use App\Modules\Valuation\Models\CostingMethodAssignment;
use App\Modules\Valuation\Models\CogsRecord;
use App\Modules\Valuation\Models\StandardCostVariance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * InventoryValuationService
 *
 * Central costing engine. Resolves and applies the correct valuation method
 * per product/warehouse at runtime. All costing writes are atomic (DB transactions).
 *
 * Supported Methods:
 *   - FIFO  : Consume oldest layers first
 *   - LIFO  : Consume newest layers first
 *   - AVCO  : Recalculate weighted average on each receipt
 *   - Standard Cost : Fixed cost; record PPV variances
 *   - Specific ID   : Serial/lot level exact cost
 *   - FEFO  : First Expiry First Out (alias for FIFO sorted by expiry)
 */
class InventoryValuationService
{
    /**
     * Resolve the effective costing method for a product/warehouse combination.
     * Priority: variant > product > category > warehouse > organization > global
     */
    public function resolveMethod(
        int $productId,
        int $warehouseId,
        ?int $variantId = null,
        ?int $categoryId = null,
        ?int $organizationId = null,
        string $tenantId = null
    ): string {
        $assignment = CostingMethodAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->where(function ($q) use ($productId, $variantId, $categoryId, $warehouseId, $organizationId) {
                $q->where(function ($q) use ($variantId) {
                    $q->whereNotNull('variant_id')->where('variant_id', $variantId);
                })->orWhere(function ($q) use ($productId) {
                    $q->whereNotNull('product_id')->whereNull('variant_id')->where('product_id', $productId);
                })->orWhere(function ($q) use ($categoryId) {
                    $q->whereNotNull('category_id')->whereNull('product_id')->where('category_id', $categoryId);
                })->orWhere(function ($q) use ($warehouseId) {
                    $q->whereNotNull('warehouse_id')->whereNull('category_id')->where('warehouse_id', $warehouseId);
                })->orWhere(function ($q) use ($organizationId) {
                    $q->whereNotNull('organization_id')->whereNull('warehouse_id')->where('organization_id', $organizationId);
                })->orWhere(function ($q) {
                    $q->whereNull('organization_id')->whereNull('warehouse_id');
                });
            })
            ->orderByRaw("
                CASE
                    WHEN variant_id IS NOT NULL THEN 1
                    WHEN product_id IS NOT NULL AND variant_id IS NULL THEN 2
                    WHEN category_id IS NOT NULL THEN 3
                    WHEN warehouse_id IS NOT NULL THEN 4
                    WHEN organization_id IS NOT NULL THEN 5
                    ELSE 6
                END ASC
            ")
            ->first();

        return $assignment?->costing_method ?? config('wims.default_costing_method', 'avco');
    }

    /**
     * Record a stock RECEIPT (creates/updates valuation layer).
     *
     * @param array $params {
     *   product_id, variant_id, warehouse_id, lot_id, qty, unit_cost,
     *   uom_id, currency_id, reference_type, reference_id, layer_date
     * }
     */
    public function recordReceipt(array $params): array
    {
        return DB::transaction(function () use ($params) {
            $method = $this->resolveMethod(
                $params['product_id'],
                $params['warehouse_id'],
                $params['variant_id'] ?? null
            );

            $result = match ($method) {
                'fifo', 'fefo' => $this->createFifoLayer($params, $method),
                'lifo'         => $this->createLifoLayer($params),
                'avco'         => $this->updateAvco($params),
                'standard'     => $this->recordStandardCostReceipt($params),
                'specific_identification' => $this->createSpecificLayer($params),
                default        => $this->createFifoLayer($params, 'fifo'),
            };

            $this->updateStockLevelCost($params['product_id'], $params['warehouse_id'], $params);

            return ['method' => $method, 'layer' => $result];
        });
    }

    /**
     * Record a stock ISSUE/DELIVERY (consumes valuation layers, generates COGS).
     *
     * @param array $params {
     *   product_id, variant_id, warehouse_id, lot_id, qty, uom_id,
     *   sales_price, reference_type, reference_id, transaction_date
     * }
     * @return array ['unit_cost' => ..., 'total_cogs' => ..., 'layers_consumed' => [...]]
     */
    public function recordIssue(array $params): array
    {
        return DB::transaction(function () use ($params) {
            $method = $this->resolveMethod(
                $params['product_id'],
                $params['warehouse_id'],
                $params['variant_id'] ?? null
            );

            $cogsInfo = match ($method) {
                'fifo'         => $this->consumeFifoLayers($params),
                'fefo'         => $this->consumeFefoLayers($params),
                'lifo'         => $this->consumeLifoLayers($params),
                'avco'         => $this->issueAtAvco($params),
                'standard'     => $this->issueAtStandardCost($params),
                'specific_identification' => $this->issueAtSpecificCost($params),
                default        => $this->consumeFifoLayers($params),
            };

            // Record COGS entry
            $this->createCogsRecord($params, $cogsInfo, $method);

            return $cogsInfo;
        });
    }

    /**
     * Record a RETURN to stock (reverse COGS, adjust/create new layer).
     *
     * @param string $returnType 'sales_return' | 'supplier_return'
     */
    public function recordReturn(array $params, string $returnType): array
    {
        return DB::transaction(function () use ($params, $returnType) {
            $method = $this->resolveMethod(
                $params['product_id'],
                $params['warehouse_id'],
                $params['variant_id'] ?? null
            );

            if ($returnType === 'supplier_return') {
                // Reverse the original receipt layer (reduce it)
                return $this->reverseReceiptLayer($params, $method);
            }

            // Sales return: put stock back at original cost or current AVCO
            return $this->restock($params, $method);
        });
    }

    // ── Private Layer Operations ────────────────────────────────────────────

    private function createFifoLayer(array $p, string $method): InventoryValuationLayer
    {
        return InventoryValuationLayer::create([
            'tenant_id'       => $p['tenant_id'] ?? null,
            'product_id'      => $p['product_id'],
            'variant_id'      => $p['variant_id'] ?? null,
            'warehouse_id'    => $p['warehouse_id'],
            'lot_id'          => $p['lot_id'] ?? null,
            'costing_method'  => $method,
            'layer_type'      => 'receipt',
            'reference_type'  => $p['reference_type'],
            'reference_id'    => $p['reference_id'],
            'initial_qty'     => $p['qty'],
            'remaining_qty'   => $p['qty'],
            'unit_cost'       => $p['unit_cost'],
            'total_cost'      => bcmul($p['qty'], $p['unit_cost'], 6),
            'remaining_value' => bcmul($p['qty'], $p['unit_cost'], 6),
            'uom_id'          => $p['uom_id'],
            'currency_id'     => $p['currency_id'] ?? null,
            'layer_date'      => $p['layer_date'] ?? now(),
            'sequence'        => $this->nextLayerSequence($p['product_id'], $p['warehouse_id'], $p['layer_date'] ?? now()),
        ]);
    }

    private function createLifoLayer(array $p): InventoryValuationLayer
    {
        // Same structure as FIFO; difference is in consumption order
        return $this->createFifoLayer($p, 'lifo');
    }

    private function createSpecificLayer(array $p): InventoryValuationLayer
    {
        return $this->createFifoLayer($p, 'specific_identification');
    }

    private function updateAvco(array $p): array
    {
        $current = StockLevel::where([
            'product_id'  => $p['product_id'],
            'variant_id'  => $p['variant_id'] ?? null,
            'warehouse_id' => $p['warehouse_id'],
        ])->first();

        $currentQty   = $current?->qty_on_hand ?? 0;
        'currentValue = bcmul($currentQty, $current?->unit_cost ?? 0, 6);
        $newQty        = bcadd($currentQty, $p['qty'], 6);
        $newValue      = bcadd($currentValue, bcmul($p['qty'], $p['unit_cost'], 6), 6);
        $newAvco       = $newQty > 0 ? bcdiv($newValue, $newQty, 10) : $p['unit_cost'];

        // Record AVCO history snapshot
        AvgCostHistory::create([
            'tenant_id'          => $p['tenant_id'] ?? null,
            'product_id'         => $p['product_id'],
            'variant_id'         => $p['variant_id'] ?? null,
            'warehouse_id'       => $p['warehouse_id'],
            'uom_id'             => $p['uom_id'],
            'trigger_type'       => 'receipt',
            'trigger_id'         => $p['reference_id'],
            'qty_before'         => $currentQty,
            'avco_before'        => $current?->unit_cost ?? 0,
            'total_value_before' => $currentValue,
            'transaction_qty'    => $p['qty'],
            'transaction_cost'   => $p['unit_cost'],
            'qty_after'          => $newQty,
            'avco_after'         => $newAvco,
            'total_value_after'  => $newValue,
            'currency_id'        => $p['currency_id'] ?? null,
            'occurred_at'        => $p['layer_date'] ?? now(),
        ]);

        return ['new_avco' => $newAvco, 'qty_after' => $newQty, 'value_after' => $newValue];
    }

    private function recordStandardCostReceipt(array $p): array
    {
        $standardCost = $this->getStandardCost($p['product_id'], $p['warehouse_id'], $p['variant_id'] ?? null);
        $variance     = bcsub($p['unit_cost'], $standardCost, 6);
        $totalVariance = bcmul($variance, $p['qty'], 6);

        if (abs($totalVariance) > 0.0001) {
            StandardCostVariance::create([
                'tenant_id'     => $p['tenant_id'] ?? null,
                'product_id'    => $p['product_id'],
                'variant_id'    => $p['variant_id'] ?? null,
                'warehouse_id'  => $p['warehouse_id'],
                'variance_type' => 'purchase_price',
                'source_type'   => $p['reference_type'],
                'source_id'     => $p['reference_id'],
                'standard_cost' => $standardCost,
                'actual_cost'   => $p['unit_cost'],
                'quantity'      => $p['qty'],
                'variance_per_unit' => $variance,
                'total_variance'    => $totalVariance,
                'currency_id'       => $p['currency_id'] ?? null,
                'occurred_at'       => $p['layer_date'] ?? now(),
            ]);
        }

        // Create layer at STANDARD cost (not actual)
        return ['layer' => $this->createFifoLayer(array_merge($p, ['unit_cost' => $standardCost]), 'standard')];
    }

    private function consumeFifoLayers(array $p): array
    {
        return $this->consumeLayers($p, 'asc');
    }

    private function consumeLifoLayers(array $p): array
    {
        return $this->consumeLayers($p, 'desc');
    }

    private function consumeFefoLayers(array $p): array
    {
        // Join with tracking_lots to sort by expiry_date ASC
        $remainingQty = $p['qty'];
        $consumed     = [];
        $totalCogs    = 0;

        $layers = InventoryValuationLayer::join('tracking_lots', 'tracking_lots.id', '=', 'inventory_valuation_layers.lot_id')
            ->where('inventory_valuation_layers.product_id', $p['product_id'])
            ->where('inventory_valuation_layers.warehouse_id', $p['warehouse_id'])
            ->where('inventory_valuation_layers.is_fully_consumed', false)
            ->where('inventory_valuation_layers.remaining_qty', '>', 0)
            ->orderBy('tracking_lots.expiry_date', 'asc')
            ->orderBy('inventory_valuation_layers.layer_date', 'asc')
            ->select('inventory_valuation_layers.*')
            ->lockForUpdate()
            ->get();

        return $this->processLayerConsumption($layers, $remainingQty, $p);
    }

    private function consumeLayers(array $p, string $direction): array
    {
        $layers = InventoryValuationLayer::where('product_id', $p['product_id'])
            ->where('warehouse_id', $p['warehouse_id'])
            ->when($p['lot_id'] ?? null, fn($q) => $q->where('lot_id', $p['lot_id']))
            ->where('is_fully_consumed', false)
            ->where('remaining_qty', '>', 0)
            ->orderBy('layer_date', $direction)
            ->orderBy('sequence', $direction)
            ->lockForUpdate()
            ->get();

        return $this->processLayerConsumption($layers, $p['qty'], $p);
    }

    private function processLayerConsumption($layers, float $remainingQty, array $p): array
    {
        $consumed  = [];
        $totalCogs = 0;

        foreach ($layers as $layer) {
            if ($remainingQty <= 0) break;

            $consumeQty = min($remainingQty, $layer->remaining_qty);
            $costImpact = bcmul($consumeQty, $layer->unit_cost, 6);

            $layer->remaining_qty   = bcsub($layer->remaining_qty, $consumeQty, 6);
            $layer->remaining_value = bcmul($layer->remaining_qty, $layer->unit_cost, 6);
            $layer->is_fully_consumed = $layer->remaining_qty <= 0;
            if ($layer->is_fully_consumed) {
                $layer->fully_consumed_at = now();
            }
            $layer->save();

            $consumed[]    = ['layer_id' => $layer->id, 'qty' => $consumeQty, 'unit_cost' => $layer->unit_cost, 'total' => $costImpact];
            $totalCogs     = bcadd($totalCogs, $costImpact, 6);
            $remainingQty  = bcsub($remainingQty, $consumeQty, 6);
        }

        $issuedQty = bcsub($p['qty'], $remainingQty, 6);
        $unitCogs  = $issuedQty > 0 ? bcdiv($totalCogs, $issuedQty, 10) : 0;

        return [
            'unit_cost'       => $unitCogs,
            'total_cogs'      => $totalCogs,
            'layers_consumed' => $consumed,
            'unfulfilled_qty' => $remainingQty,
        ];
    }

    private function issueAtAvco(array $p): array
    {
        $stock = StockLevel::where([
            'product_id'   => $p['product_id'],
            'warehouse_id' => $p['warehouse_id'],
        ])->first();

        $unitCost  = $stock?->unit_cost ?? 0;
        $totalCogs = bcmul($p['qty'], $unitCost, 6);

        return ['unit_cost' => $unitCost, 'total_cogs' => $totalCogs, 'layers_consumed' => []];
    }

    private function issueAtStandardCost(array $p): array
    {
        $stdCost   = $this->getStandardCost($p['product_id'], $p['warehouse_id'], $p['variant_id'] ?? null);
        $totalCogs = bcmul($p['qty'], $stdCost, 6);

        return ['unit_cost' => $stdCost, 'total_cogs' => $totalCogs, 'layers_consumed' => []];
    }

    private function issueAtSpecificCost(array $p): array
    {
        // Must have lot_id or serial_number_id to identify specific cost
        $layer = InventoryValuationLayer::where('product_id', $p['product_id'])
            ->where('warehouse_id', $p['warehouse_id'])
            ->where('lot_id', $p['lot_id'])
            ->where('is_fully_consumed', false)
            ->first();

        $unitCost  = $layer?->unit_cost ?? 0;
        $totalCogs = bcmul($p['qty'], $unitCost, 6);

        if ($layer) {
            $layer->remaining_qty   = bcsub($layer->remaining_qty, $p['qty'], 6);
            $layer->remaining_value = bcmul($layer->remaining_qty, $layer->unit_cost, 6);
            $layer->is_fully_consumed = $layer->remaining_qty <= 0;
            $layer->save();
        }

        return ['unit_cost' => $unitCost, 'total_cogs' => $totalCogs, 'layers_consumed' => $layer ? [$layer->id] : []];
    }

    private function reverseReceiptLayer(array $p, string $method): array
    {
        // Reduce the matching layer (for supplier returns)
        $layer = InventoryValuationLayer::where('product_id', $p['product_id'])
            ->where('warehouse_id', $p['warehouse_id'])
            ->when($p['lot_id'] ?? null, fn($q) => $q->where('lot_id', $p['lot_id']))
            ->where('costing_method', $method)
            ->latest('layer_date')
            ->lockForUpdate()
            ->first();

        if ($layer) {
            $reverseQty = min($p['qty'], $layer->remaining_qty);
            $layer->remaining_qty   = bcsub($layer->remaining_qty, $reverseQty, 6);
            $layer->remaining_value = bcmul($layer->remaining_qty, $layer->unit_cost, 6);
            $layer->is_fully_consumed = $layer->remaining_qty <= 0;
            $layer->save();

            return ['reversed_qty' => $reverseQty, 'unit_cost' => $layer->unit_cost];
        }

        return ['reversed_qty' => 0, 'unit_cost' => 0];
    }

    private function restock(array $p, string $method): array
    {
        // For sales returns: create a new receipt layer at original cost or AVCO
        $unitCost = $p['original_unit_cost'] ?? $this->getCurrentAvco($p['product_id'], $p['warehouse_id']);

        return $this->recordReceipt(array_merge($p, [
            'unit_cost'  => $unitCost,
            'layer_date' => now(),
        ]));
    }

    private function createCogsRecord(array $p, array $cogsInfo, string $method): CogsRecord
    {
        return CogsRecord::create([
            'tenant_id'       => $p['tenant_id'] ?? null,
            'warehouse_id'    => $p['warehouse_id'],
            'source_type'     => $p['reference_type'],
            'source_id'       => $p['reference_id'],
            'product_id'      => $p['product_id'],
            'variant_id'      => $p['variant_id'] ?? null,
            'lot_id'          => $p['lot_id'] ?? null,
            'customer_id'     => $p['customer_id'] ?? null,
            'sales_order_id'  => $p['sales_order_id'] ?? null,
            'qty'             => $p['qty'],
            'uom_id'          => $p['uom_id'],
            'costing_method'  => $method,
            'unit_cost'       => $cogsInfo['unit_cost'],
            'total_cogs'      => $cogsInfo['total_cogs'],
            'sales_price'     => $p['sales_price'] ?? null,
            'gross_profit'    => isset($p['sales_price']) ? bcsub(bcmul($p['qty'], $p['sales_price'], 6), $cogsInfo['total_cogs'], 6) : null,
            'currency_id'     => $p['currency_id'] ?? null,
            'transaction_date' => $p['transaction_date'] ?? now(),
        ]);
    }

    private function updateStockLevelCost(int $productId, int $warehouseId, array $p): void
    {
        StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($p['variant_id'] ?? null, fn($q) => $q->where('variant_id', $p['variant_id']))
            ->update([
                'unit_cost'   => $this->getCurrentAvco($productId, $warehouseId),
                'last_move_date' => now(),
            ]);
    }

    private function getCurrentAvco(int $productId, int $warehouseId): float
    {
        $stock = StockLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return (float) ($stock?->unit_cost ?? 0);
    }

    private function getStandardCost(int $productId, int $warehouseId, ?int $variantId = null): float
    {
        $sc = \App\Modules\Inventory\Models\StandardCost::where('product_id', $productId)
            ->where('is_current', true)
            ->latest('effective_from')
            ->first();

        return (float) ($sc?->standard_cost ?? 0);
    }

    private function nextLayerSequence(int $productId, int $warehouseId, $date): int
    {
        return InventoryValuationLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereDate('layer_date', $date)
            ->max('sequence') + 1;
    }
}
