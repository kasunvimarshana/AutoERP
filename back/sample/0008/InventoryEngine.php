<?php

namespace App\Services\Inventory;

use App\Models\{Product, ProductVariant, Warehouse, StockPosition, StockLedgerEntry, CostingLayer, CostingLayerConsumption, InventorySettings, Organization};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * InventoryEngine
 *
 * The core service that handles all inventory movements with full support for:
 *
 * VALUATION METHODS:
 *   - FIFO  (First In First Out)
 *   - LIFO  (Last In First Out)
 *   - AVCO  (Weighted Average Cost)
 *   - FEFO  (First Expired First Out)
 *   - FMFO  (First Manufactured First Out)
 *   - Specific Identification
 *   - Standard Cost
 *   - Retail Method
 *
 * STOCK ROTATION STRATEGIES (physical picking order):
 *   - FIFO | LIFO | FEFO | FMFO | LEFO
 *
 * ALLOCATION ALGORITHMS:
 *   - strict_reservation  → hard lock on specific lot/location/quantity
 *   - soft_reservation    → claim quantity without locking location
 *   - fair_share          → distribute available stock proportionally across orders
 *   - priority_based      → highest-priority orders get stock first
 *   - wave_picking        → group orders into waves for warehouse efficiency
 *   - zone_picking        → assign pickers by warehouse zone
 *   - batch_picking       → picker collects for multiple orders simultaneously
 *   - cluster_picking     → cart-based multi-order picking
 */
class InventoryEngine
{
    public function __construct(
        private ValuationService     $valuationService,
        private AllocationService    $allocationService,
        private RotationService      $rotationService,
        private LedgerService        $ledgerService,
    ) {}

    // ═══════════════════════════════════════════════════════════════════════
    // STOCK IN — Purchase Receipt, Production Output, Return from Customer
    // ═══════════════════════════════════════════════════════════════════════

    public function receiveStock(array $params): StockLedgerEntry
    {
        return DB::transaction(function () use ($params) {
            $this->validateParams($params, ['product_id', 'warehouse_id', 'quantity', 'unit_cost', 'movement_type']);

            $product     = Product::findOrFail($params['product_id']);
            $settings    = $this->getSettings($product->organization_id);
            $method      = $params['valuation_method'] ?? $this->resolveValuationMethod($product, $settings);

            // 1. Update or create stock position
            $position = $this->upsertPosition($params, 'IN');

            // 2. Write to ledger (immutable)
            $entry = $this->ledgerService->write($params, $position, $method, 'IN');

            // 3. Create costing layer for FIFO / LIFO / FEFO / specific_id
            if (in_array($method, ['FIFO', 'LIFO', 'FEFO', 'FMFO', 'specific_id'])) {
                $this->valuationService->createLayer($entry, $params, $method);
            }

            // 4. Update AVCO (recalculate running weighted average)
            if ($method === 'AVCO') {
                $this->valuationService->recalculateAvco($position, $params['quantity'], $params['unit_cost']);
            }

            // 5. Batch / Lot quantity tracking
            if (!empty($params['lot_id'])) {
                $this->updateLotQuantity($params['lot_id'], $params['quantity'], 'IN');
            }

            // 6. Trigger reorder-point check
            $this->checkAlerts($product, $position, $settings);

            return $entry;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STOCK OUT — Sales Issue, Production Consume, Write-off
    // ═══════════════════════════════════════════════════════════════════════

    public function issueStock(array $params): StockLedgerEntry
    {
        return DB::transaction(function () use ($params) {
            $this->validateParams($params, ['product_id', 'warehouse_id', 'quantity', 'movement_type']);

            $product  = Product::findOrFail($params['product_id']);
            $settings = $this->getSettings($product->organization_id);
            $method   = $params['valuation_method'] ?? $this->resolveValuationMethod($product, $settings);

            // 1. Determine picking order based on rotation strategy
            $rotation = $params['rotation_strategy'] ?? $settings->default_stock_rotation ?? 'FIFO';
            $lots     = $this->rotationService->getLotPickingSequence(
                productId:  $params['product_id'],
                variantId:  $params['product_variant_id'] ?? null,
                warehouseId: $params['warehouse_id'],
                quantity:   $params['quantity'],
                strategy:   $rotation,
            );

            // 2. Validate availability
            $available = $this->getAvailableQuantity($params['product_id'], $params['warehouse_id'], $params['product_variant_id'] ?? null);
            if (!($settings->allow_negative_stock) && $available < $params['quantity']) {
                throw new \RuntimeException("Insufficient stock. Available: {$available}, Requested: {$params['quantity']}");
            }

            // 3. Resolve unit cost from valuation layers
            $unitCost = $this->valuationService->resolveIssueCost(
                productId:   $params['product_id'],
                variantId:   $params['product_variant_id'] ?? null,
                warehouseId: $params['warehouse_id'],
                quantity:    $params['quantity'],
                method:      $method,
                lotId:       $params['lot_id'] ?? null,
                batchId:     $params['batch_id'] ?? null,
            );

            $params['unit_cost']       = $unitCost['unit_cost'];
            $params['total_cost']      = $unitCost['total_cost'];
            $params['consumed_layers'] = $unitCost['consumed_layers'] ?? [];

            // 4. Update position
            $position = $this->upsertPosition($params, 'OUT');

            // 5. Write ledger
            $entry = $this->ledgerService->write($params, $position, $method, 'OUT');

            // 6. Consume costing layers & record consumption trail
            foreach ($params['consumed_layers'] as $consumption) {
                CostingLayerConsumption::create([
                    'costing_layer_id'  => $consumption['layer_id'],
                    'ledger_entry_id'   => $entry->id,
                    'quantity_consumed' => $consumption['quantity'],
                    'unit_cost'         => $consumption['unit_cost'],
                    'total_cost'        => $consumption['total_cost'],
                ]);

                CostingLayer::where('id', $consumption['layer_id'])
                    ->decrement('remaining_quantity', $consumption['quantity']);

                CostingLayer::where('id', $consumption['layer_id'])
                    ->where('remaining_quantity', '<=', 0)
                    ->update(['is_fully_consumed' => true, 'fully_consumed_at' => now()]);
            }

            // 7. Update AVCO
            if ($method === 'AVCO') {
                $this->valuationService->recalculateAvco($position, -$params['quantity'], $unitCost['unit_cost']);
            }

            // 8. Lot quantity
            if (!empty($params['lot_id'])) {
                $this->updateLotQuantity($params['lot_id'], $params['quantity'], 'OUT');
            }

            // 9. Serial number status update
            if (!empty($params['serial_number_id'])) {
                \App\Models\SerialNumber::where('id', $params['serial_number_id'])
                    ->update(['status' => 'sold', 'sold_at' => now()]);
            }

            // 10. Alerts
            $this->checkAlerts($product, $position, $settings);

            return $entry;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TRANSFER — Move stock between warehouses or locations
    // ═══════════════════════════════════════════════════════════════════════

    public function transferStock(array $params): array
    {
        return DB::transaction(function () use ($params) {
            // Issue from source
            $outEntry = $this->issueStock(array_merge($params, [
                'warehouse_id'  => $params['source_warehouse_id'],
                'movement_type' => 'transfer_out',
            ]));

            // Receive at destination (using same cost to avoid P&L impact)
            $inEntry = $this->receiveStock(array_merge($params, [
                'warehouse_id'  => $params['destination_warehouse_id'],
                'movement_type' => 'transfer_in',
                'unit_cost'     => $outEntry->unit_cost,
            ]));

            return ['out' => $outEntry, 'in' => $inEntry];
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADJUSTMENT — Write up/down, revaluation, count corrections
    // ═══════════════════════════════════════════════════════════════════════

    public function adjustStock(array $params): StockLedgerEntry
    {
        $direction = $params['quantity_adjusted'] >= 0 ? 'IN' : 'OUT';
        $params['quantity']      = abs($params['quantity_adjusted']);
        $params['movement_type'] = $direction === 'IN' ? 'adjustment_positive' : 'adjustment_negative';
        $params['unit_cost']     = $params['unit_cost'] ?? $this->getCurrentAverageCost($params['product_id'], $params['warehouse_id']);

        return $direction === 'IN'
            ? $this->receiveStock($params)
            : $this->issueStock($params);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    private function upsertPosition(array $params, string $direction): StockPosition
    {
        $key = [
            'product_id'         => $params['product_id'],
            'product_variant_id' => $params['product_variant_id'] ?? null,
            'warehouse_id'       => $params['warehouse_id'],
            'storage_location_id'=> $params['storage_location_id'] ?? null,
            'lot_id'             => $params['lot_id'] ?? null,
            'batch_id'           => $params['batch_id'] ?? null,
        ];

        $position = StockPosition::firstOrCreate(
            $key,
            array_merge($key, [
                'organization_id' => $params['organization_id'],
                'uom_id'          => $params['uom_id'] ?? null,
                'qty_on_hand'     => 0,
                'qty_available'   => 0,
                'average_cost'    => $params['unit_cost'] ?? 0,
            ])
        );

        $qty = $params['quantity'];
        if ($direction === 'IN') {
            $position->increment('qty_on_hand', $qty);
            $position->increment('qty_available', $qty);
        } else {
            $position->decrement('qty_on_hand', $qty);
            $position->decrement('qty_available', $qty);
        }

        $position->update([
            'last_movement_at' => now(),
            'total_cost_value' => DB::raw('qty_on_hand * average_cost'),
        ]);

        return $position->fresh();
    }

    private function updateLotQuantity(int $lotId, float $quantity, string $direction): void
    {
        $field = 'available_quantity';
        if ($direction === 'IN') {
            \App\Models\Lot::where('id', $lotId)->increment($field, $quantity);
        } else {
            \App\Models\Lot::where('id', $lotId)->decrement($field, $quantity);
        }
    }

    private function resolveValuationMethod(Product $product, InventorySettings $settings): string
    {
        return $product->valuation_method
            ?? $product->warehouse?->valuation_method
            ?? $settings->default_valuation_method
            ?? 'AVCO';
    }

    private function getSettings(int $organizationId): InventorySettings
    {
        return \App\Models\InventorySettings::where('organization_id', $organizationId)->firstOrFail();
    }

    private function getAvailableQuantity(int $productId, int $warehouseId, ?int $variantId = null): float
    {
        return StockPosition::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->sum('qty_available');
    }

    private function getCurrentAverageCost(int $productId, int $warehouseId): float
    {
        return StockPosition::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->avg('average_cost') ?? 0;
    }

    private function checkAlerts(Product $product, StockPosition $position, InventorySettings $settings): void
    {
        // Delegate to a dedicated AlertService (implementation injected)
        app(\App\Services\Inventory\AlertService::class)->evaluate($product, $position, $settings);
    }

    private function validateParams(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Missing required parameter: {$field}");
            }
        }
    }
}
