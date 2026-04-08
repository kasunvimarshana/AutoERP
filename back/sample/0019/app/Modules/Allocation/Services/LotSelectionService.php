<?php

namespace App\Modules\Allocation\Services;

use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\TrackingLot;
use Illuminate\Support\Facades\DB;

/**
 * LotSelectionService
 *
 * Implements all stock rotation strategies for lot/location selection
 * during picking and allocation.
 *
 * Strategies:
 *   FIFO  — First In, First Out (oldest receipt date first)
 *   LIFO  — Last In, First Out (newest receipt date first)
 *   FEFO  — First Expiry First Out (nearest expiry first)
 *   LEFO  — Last Expiry First Out (furthest expiry first)
 *   FMFO  — First Manufactured First Out (oldest manufacture date)
 *   SLED  — Shortest Life / Expiry Date (FEFO variant, strict)
 *   Manual — No auto-selection
 */
class LotSelectionService
{
    /**
     * Select optimal lot(s) to fulfil a given quantity.
     * Returns array of {lot_id, location_id, qty} entries.
     */
    public function selectLots(
        int    $productId,
        ?int   $variantId,
        int    $warehouseId,
        float  $qty,
        string $strategy = 'fifo',
        array  $context  = []
    ): array {
        $availableStock = $this->getAvailableStock($productId, $variantId, $warehouseId, $strategy, $context);

        $remainingQty  = $qty;
        $selectedLots  = [];

        foreach ($availableStock as $stock) {
            if ($remainingQty <= 0) break;

            // Skip lots blocked by expiry margin
            if (isset($context['expiry_pick_margin_days']) && $context['expiry_pick_margin_days'] > 0) {
                if ($stock->expiry_date && Carbon::parse($stock->expiry_date)->diffInDays(now()) <= $context['expiry_pick_margin_days']) {
                    continue;
                }
            }

            // Skip quarantined lots
            if ($stock->lot_status === 'quarantine') {
                continue;
            }

            $availableQty = min($stock->qty_available, $remainingQty);

            $selectedLots[] = [
                'lot_id'      => $stock->lot_id,
                'location_id' => $stock->location_id,
                'qty'         => $availableQty,
                'unit_cost'   => $stock->unit_cost,
                'expiry_date' => $stock->expiry_date ?? null,
                'receipt_date' => $stock->first_in_date ?? null,
            ];

            $remainingQty -= $availableQty;
        }

        return $selectedLots;
    }

    protected function getAvailableStock(
        int $productId, ?int $variantId, int $warehouseId, string $strategy, array $context
    ) {
        $query = StockLevel::query()
            ->select([
                'stock_levels.*',
                'tracking_lots.expiry_date',
                'tracking_lots.manufacture_date',
                'tracking_lots.best_before_date',
                'tracking_lots.receipt_date',
                'tracking_lots.status as lot_status',
            ])
            ->where('stock_levels.product_id', $productId)
            ->where('stock_levels.warehouse_id', $warehouseId)
            ->where('stock_levels.qty_available', '>', 0)
            ->leftJoin('tracking_lots', 'tracking_lots.id', '=', 'stock_levels.lot_id');

        if ($variantId) {
            $query->where('stock_levels.variant_id', $variantId);
        }

        // Exclude quarantined lots
        $query->where(function ($q) {
            $q->whereNull('tracking_lots.status')
              ->orWhereNotIn('tracking_lots.status', ['quarantine', 'rejected', 'recalled', 'expired']);
        });

        // Prefer specific location if specified
        if (! empty($context['preferred_location_ids'])) {
            $query->orderByRaw('CASE WHEN stock_levels.location_id IN (?) THEN 0 ELSE 1 END', [
                $context['preferred_location_ids'],
            ]);
        }

        // Apply rotation strategy ordering
        switch ($strategy) {
            case 'fefo':
            case 'sled':
                $query->orderByRaw('CASE WHEN tracking_lots.expiry_date IS NULL THEN 1 ELSE 0 END')
                      ->orderBy('tracking_lots.expiry_date', 'asc')
                      ->orderBy('stock_levels.first_in_date', 'asc');
                break;

            case 'lefo':
                $query->orderByRaw('CASE WHEN tracking_lots.expiry_date IS NULL THEN 0 ELSE 1 END')
                      ->orderBy('tracking_lots.expiry_date', 'desc')
                      ->orderBy('stock_levels.first_in_date', 'asc');
                break;

            case 'fmfo':
                $query->orderByRaw('CASE WHEN tracking_lots.manufacture_date IS NULL THEN 1 ELSE 0 END')
                      ->orderBy('tracking_lots.manufacture_date', 'asc')
                      ->orderBy('stock_levels.first_in_date', 'asc');
                break;

            case 'lifo':
                $query->orderBy('stock_levels.first_in_date', 'desc');
                break;

            case 'fefo_fifo':
                // FEFO first; FIFO within same expiry group
                $query->orderByRaw('CASE WHEN tracking_lots.expiry_date IS NULL THEN 1 ELSE 0 END')
                      ->orderBy('tracking_lots.expiry_date', 'asc')
                      ->orderBy('stock_levels.first_in_date', 'asc');
                break;

            default: // FIFO
                $query->orderBy('stock_levels.first_in_date', 'asc');
        }

        // Sub-sort by pick_sequence for physical efficiency
        $query->leftJoin('warehouse_locations', 'warehouse_locations.id', '=', 'stock_levels.location_id')
              ->orderBy('warehouse_locations.pick_sequence', 'asc');

        return $query->get();
    }
}
