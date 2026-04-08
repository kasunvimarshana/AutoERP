<?php

namespace App\Services;

use App\Models\{PhysicalCount, PhysicalCountItem, StockPosition, StockAdjustment, StockAdjustmentLine};
use App\Services\Inventory\InventoryEngine;
use Illuminate\Support\Facades\DB;

/**
 * PhysicalCountService
 *
 * Manages physical and cycle count workflows:
 *   Planning → In Progress → Counting → Reconciling → Approved → Posted
 *
 * Supports:
 *  - Full counts (all items in a warehouse)
 *  - Partial counts (filtered by zone, category, location)
 *  - Cycle counts (ABC, velocity, random, zone-based rotation)
 *  - Spot checks (single product or location)
 *  - Blind counting (system qty hidden from counters)
 *  - Double-blind recounts for discrepancies
 *  - Inventory freeze during count
 *  - Variance analysis and auto-adjustment generation
 */
class PhysicalCountService
{
    public function __construct(
        private InventoryEngine         $inventory,
        private DocumentSequenceService $sequences,
    ) {}

    // ── Create Count ──────────────────────────────────────────────────────────
    public function create(array $data): PhysicalCount
    {
        return DB::transaction(function () use ($data) {
            $count = PhysicalCount::create(array_merge($data, [
                'count_number' => $this->sequences->next($data['organization_id'], 'physical_count'),
                'status'       => 'planning',
                'created_by'   => auth()->id(),
            ]));

            return $count;
        });
    }

    // ── Start Count — snapshot system quantities and generate count sheets ────
    public function startCount(PhysicalCount $count): PhysicalCount
    {
        return DB::transaction(function () use ($count) {
            if ($count->status !== 'planning') {
                throw new \RuntimeException("Count must be in 'planning' status to start.");
            }

            // Build the scope query for stock positions
            $query = StockPosition::where(
                fn ($q) => $q->where('warehouse_id', $count->warehouse_id)
                    ->where('qty_on_hand', '!=', 0)
            );

            // Apply scope filters (zone, category, location, product ids)
            $filters = $count->scope_filters ?? [];
            if (!empty($filters['storage_location_ids'])) {
                $query->whereIn('storage_location_id', $filters['storage_location_ids']);
            }
            if (!empty($filters['product_ids'])) {
                $query->whereIn('product_id', $filters['product_ids']);
            }
            if (!empty($filters['category_ids'])) {
                $query->whereHas('product', fn ($q) => $q->whereIn('category_id', $filters['category_ids']));
            }

            // Apply cycle count method filtering
            if ($count->type === 'cycle') {
                $query = $this->applyCycleFilter($query, $count);
            }

            $positions = $query->get();

            // Create count item for each position (snapshot system qty)
            foreach ($positions as $position) {
                PhysicalCountItem::create([
                    'physical_count_id'  => $count->id,
                    'product_id'         => $position->product_id,
                    'product_variant_id' => $position->product_variant_id,
                    'storage_location_id'=> $position->storage_location_id,
                    'lot_id'             => $position->lot_id,
                    'batch_id'           => $position->batch_id,
                    'uom_id'             => $position->uom_id,
                    'quantity_system'    => $position->qty_on_hand, // snapshot
                    'quantity_counted'   => null,                   // to be filled by counter
                    'status'             => 'pending',
                ]);
            }

            $count->update([
                'status'     => 'in_progress',
                'started_at' => now(),
            ]);

            return $count->fresh(['items']);
        });
    }

    // ── Record Count ──────────────────────────────────────────────────────────
    public function recordCount(PhysicalCountItem $item, float $quantityCounted, int $countedBy): PhysicalCountItem
    {
        $item->update([
            'quantity_counted' => $quantityCounted,
            'counted_by'       => $countedBy,
            'counted_at'       => now(),
            'status'           => 'counted',
        ]);

        // Calculate variance (not blind-revealed until reconciliation)
        $variance = $quantityCounted - $item->quantity_system;
        $hasDiscrepancy = abs($variance) > 0.0001;

        $item->update([
            'has_discrepancy' => $hasDiscrepancy,
            'requires_recount' => $hasDiscrepancy && abs($variance) > ($this->getRecountThreshold($item)),
        ]);

        return $item->fresh();
    }

    // ── Record Recount ────────────────────────────────────────────────────────
    public function recordRecount(PhysicalCountItem $item, float $quantityRecounted, int $recountedBy): PhysicalCountItem
    {
        $item->update([
            'quantity_recounted' => $quantityRecounted,
            'recounted_by'       => $recountedBy,
            'recounted_at'       => now(),
            'status'             => 'recounted',
        ]);

        // Use recount value as authoritative
        $item->update([
            'quantity_variance' => $quantityRecounted - $item->quantity_system,
        ]);

        return $item->fresh();
    }

    // ── Reconcile — reveal variances and compute adjustments ─────────────────
    public function reconcile(PhysicalCount $count): array
    {
        $items = $count->items()
            ->whereIn('status', ['counted', 'recounted'])
            ->get();

        $variances = [];
        foreach ($items as $item) {
            $authoritative = $item->quantity_recounted ?? $item->quantity_counted;
            $variance      = $authoritative - $item->quantity_system;
            $item->update(['quantity_variance' => $variance]);

            if (abs($variance) > 0.0001) {
                $variances[] = [
                    'item'     => $item,
                    'variance' => $variance,
                ];
            }
        }

        $count->update(['status' => 'reconciling']);

        return $variances;
    }

    // ── Approve and Post — create adjustments and update ledger ──────────────
    public function approve(PhysicalCount $count): StockAdjustment
    {
        return DB::transaction(function () use ($count) {
            $items = $count->items()->whereNotNull('quantity_variance')->get();

            // Build a stock adjustment for all variances
            $adjustment = StockAdjustment::create([
                'organization_id' => $count->organization_id,
                'warehouse_id'    => $count->warehouse_id,
                'created_by'      => auth()->id(),
                'approved_by'     => auth()->id(),
                'adjustment_number' => $this->sequences->next($count->organization_id, 'adjustment'),
                'type'            => 'positive', // overridden per line
                'reason_category' => 'opening_balance', // physical count
                'status'          => 'approved',
                'adjustment_date' => today(),
                'notes'           => "Physical count #{$count->count_number}",
            ]);

            foreach ($items as $item) {
                if (abs($item->quantity_variance) <= 0.0001) continue;

                $authoritative = $item->quantity_recounted ?? $item->quantity_counted;
                $unitCost      = $this->getAverageCost($item->product_id, $count->warehouse_id);

                StockAdjustmentLine::create([
                    'stock_adjustment_id'  => $adjustment->id,
                    'product_id'           => $item->product_id,
                    'product_variant_id'   => $item->product_variant_id,
                    'storage_location_id'  => $item->storage_location_id,
                    'lot_id'               => $item->lot_id,
                    'batch_id'             => $item->batch_id,
                    'uom_id'               => $item->uom_id,
                    'quantity_system'      => $item->quantity_system,
                    'quantity_actual'      => $authoritative,
                    'quantity_adjusted'    => $item->quantity_variance,
                    'unit_cost'            => $unitCost,
                    'cost_impact'          => $item->quantity_variance * $unitCost,
                ]);

                // Post the stock movement
                $this->inventory->adjustStock([
                    'organization_id'    => $count->organization_id,
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'warehouse_id'       => $count->warehouse_id,
                    'storage_location_id'=> $item->storage_location_id,
                    'lot_id'             => $item->lot_id,
                    'batch_id'           => $item->batch_id,
                    'quantity_adjusted'  => $item->quantity_variance,
                    'unit_cost'          => $unitCost,
                    'source_document_type' => 'physical_count',
                    'source_document_id'   => $count->id,
                    'reason_code'          => 'physical_count_adjustment',
                    'movement_date'        => today(),
                ]);

                $item->update([
                    'status'   => 'approved',
                    'counted_at' => $item->counted_at ?? now(),
                ]);
            }

            $count->update([
                'status'       => 'posted',
                'approved_by'  => auth()->id(),
                'completed_at' => now(),
            ]);

            return $adjustment;
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function applyCycleFilter($query, PhysicalCount $count)
    {
        return match($count->cycle_count_method) {
            'abc' => $query->whereHas('product', function ($q) use ($count) {
                // A-class: highest value items (top 20% by annual value)
                $q->whereHas('classifications', fn ($c) => $c
                    ->where('abc_class', 'A')
                    ->where('period', now()->format('Y'))
                );
            }),
            'velocity' => $query->whereHas('product', fn ($q) => $q->whereHas('classifications', fn ($c) => $c
                ->where('velocity_class', 'fast')
                ->where('period', now()->format('Y'))
            )),
            'zone' => $query->whereHas('storageLocation', fn ($q) => $q
                ->where('warehouse_zone_id', $count->scope_filters['zone_id'] ?? null)
            ),
            default => $query,
        };
    }

    private function getRecountThreshold(PhysicalCountItem $item): float
    {
        // Recount if variance > 2% of system quantity OR > 5 units
        return max(5, $item->quantity_system * 0.02);
    }

    private function getAverageCost(int $productId, int $warehouseId): float
    {
        return StockPosition::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->avg('average_cost') ?? 0;
    }
}
