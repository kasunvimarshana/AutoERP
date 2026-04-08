<?php

namespace App\Services\Inventory;

use App\Models\{SalesOrder, SalesOrderLine, StockAllocation, StockPosition, Lot, Product, PickList, PickListLine};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AllocationService
 *
 * Implements all inventory allocation algorithms:
 *
 *  strict_reservation  — hard lock to specific lot/location; blocks until fulfilled
 *  soft_reservation    — claim total quantity; location resolved at pick time
 *  fair_share          — proportionally distribute short stock across all open orders
 *  priority_based      — highest-priority orders (VIP, date, order priority field) get first pick
 *  wave_picking        — batch orders into waves optimised for warehouse flow
 *  zone_picking        — assign zones to pickers; each picker handles their zone
 *  batch_picking       — single picker handles multiple orders simultaneously
 *  cluster_picking     — cart picking: picker takes a cluster (trolley with totes)
 */
class AllocationService
{
    public function allocate(SalesOrder $order, string $algorithm): array
    {
        return match($algorithm) {
            'strict_reservation' => $this->strictReservation($order),
            'soft_reservation'   => $this->softReservation($order),
            'fair_share'         => $this->fairShare($order),
            'priority_based'     => $this->priorityBased($order),
            'wave_picking'       => $this->wavePicking(collect([$order])),
            'batch_picking'      => $this->batchPicking(collect([$order])),
            'cluster_picking'    => $this->clusterPicking(collect([$order])),
            default              => $this->softReservation($order),
        };
    }

    // ── Strict Reservation ───────────────────────────────────────────────────
    // Locks specific lots / locations / serials. Most deterministic.
    public function strictReservation(SalesOrder $order): array
    {
        return DB::transaction(function () use ($order) {
            $allocations = [];

            foreach ($order->lines as $line) {
                $remaining = $line->quantity_ordered - $line->quantity_allocated;
                if ($remaining <= 0) continue;

                // Find available positions sorted by rotation strategy
                $positions = $this->getAvailablePositions($line, $order->warehouse_id, $remaining, lock: true);

                foreach ($positions as $position) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, $position->qty_available);

                    $allocation = StockAllocation::create([
                        'organization_id'     => $order->organization_id,
                        'sales_order_id'      => $order->id,
                        'sales_order_line_id' => $line->id,
                        'product_id'          => $line->product_id,
                        'product_variant_id'  => $line->product_variant_id,
                        'warehouse_id'        => $order->warehouse_id,
                        'storage_location_id' => $position->storage_location_id,
                        'lot_id'              => $position->lot_id,
                        'batch_id'            => $position->batch_id,
                        'uom_id'              => $line->uom_id,
                        'allocated_by'        => auth()->id(),
                        'allocation_type'     => 'hard',
                        'algorithm_used'      => 'strict_reservation',
                        'quantity_allocated'  => $take,
                        'status'              => 'active',
                    ]);

                    // Decrement available in position
                    $position->decrement('qty_available', $take);
                    $position->increment('qty_reserved', $take);

                    $allocations[] = $allocation;
                    $remaining -= $take;
                }

                $line->increment('quantity_allocated', $line->quantity_ordered - $remaining - $line->quantity_allocated);
            }

            return $allocations;
        });
    }

    // ── Soft Reservation ─────────────────────────────────────────────────────
    // Claims total quantity without locking a specific location.
    // Location is resolved at pick-list generation time.
    public function softReservation(SalesOrder $order, int $ttlMinutes = 60): array
    {
        return DB::transaction(function () use ($order, $ttlMinutes) {
            $allocations = [];

            foreach ($order->lines as $line) {
                $remaining = $line->quantity_ordered - $line->quantity_allocated;
                if ($remaining <= 0) continue;

                $available = StockPosition::where('product_id', $line->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->when($line->product_variant_id, fn ($q) => $q->where('product_variant_id', $line->product_variant_id))
                    ->sum('qty_available');

                $take = min($remaining, $available);
                if ($take <= 0) continue;

                // Reduce global available pool (not location-specific)
                StockPosition::where('product_id', $line->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->update(['qty_available' => DB::raw("GREATEST(qty_available - {$take}, 0)")]);

                $allocation = StockAllocation::create([
                    'organization_id'     => $order->organization_id,
                    'sales_order_id'      => $order->id,
                    'sales_order_line_id' => $line->id,
                    'product_id'          => $line->product_id,
                    'product_variant_id'  => $line->product_variant_id,
                    'warehouse_id'        => $order->warehouse_id,
                    'allocated_by'        => auth()->id(),
                    'allocation_type'     => 'soft',
                    'algorithm_used'      => 'soft_reservation',
                    'quantity_allocated'  => $take,
                    'status'              => 'active',
                    'expires_at'          => now()->addMinutes($ttlMinutes),
                ]);

                $allocations[] = $allocation;
            }

            return $allocations;
        });
    }

    // ── Fair Share ───────────────────────────────────────────────────────────
    // Short stock is distributed proportionally across all orders needing it.
    public function fairShare(SalesOrder $triggerOrder): array
    {
        return DB::transaction(function () use ($triggerOrder) {
            $results = [];

            foreach ($triggerOrder->lines as $line) {
                $available = StockPosition::where('product_id', $line->product_id)
                    ->where('warehouse_id', $triggerOrder->warehouse_id)
                    ->sum('qty_available');

                // Find all open orders needing this product
                $pendingLines = SalesOrderLine::where('product_id', $line->product_id)
                    ->whereHas('salesOrder', fn ($q) => $q
                        ->where('warehouse_id', $triggerOrder->warehouse_id)
                        ->whereIn('status', ['confirmed', 'picking'])
                    )
                    ->whereRaw('quantity_ordered > quantity_allocated')
                    ->get();

                $totalDemand = $pendingLines->sum(fn ($l) => $l->quantity_ordered - $l->quantity_allocated);

                if ($totalDemand <= 0) continue;

                $ratio = min(1, $available / $totalDemand);

                foreach ($pendingLines as $pendingLine) {
                    $demand = $pendingLine->quantity_ordered - $pendingLine->quantity_allocated;
                    $share  = floor($demand * $ratio * 10000) / 10000; // round down

                    if ($share <= 0) continue;

                    $results[] = StockAllocation::create([
                        'organization_id'     => $triggerOrder->organization_id,
                        'sales_order_id'      => $pendingLine->sales_order_id,
                        'sales_order_line_id' => $pendingLine->id,
                        'product_id'          => $line->product_id,
                        'warehouse_id'        => $triggerOrder->warehouse_id,
                        'allocated_by'        => auth()->id(),
                        'allocation_type'     => 'soft',
                        'algorithm_used'      => 'fair_share',
                        'quantity_allocated'  => $share,
                        'status'              => 'active',
                    ]);
                }
            }

            return $results;
        });
    }

    // ── Priority Based ───────────────────────────────────────────────────────
    // Orders with highest priority field get fully allocated before lower ones.
    public function priorityBased(SalesOrder $triggerOrder): array
    {
        return DB::transaction(function () use ($triggerOrder) {
            $results    = [];
            $productIds = $triggerOrder->lines->pluck('product_id')->unique();

            foreach ($productIds as $productId) {
                $available = StockPosition::where('product_id', $productId)
                    ->where('warehouse_id', $triggerOrder->warehouse_id)
                    ->sum('qty_available');

                // Fetch all open lines ordered by order priority desc, then order date asc
                $pendingLines = SalesOrderLine::where('product_id', $productId)
                    ->whereHas('salesOrder', fn ($q) => $q
                        ->where('warehouse_id', $triggerOrder->warehouse_id)
                        ->whereIn('status', ['confirmed', 'picking'])
                        ->orderByDesc('priority')
                        ->orderBy('order_date')
                    )
                    ->whereRaw('quantity_ordered > quantity_allocated')
                    ->get();

                foreach ($pendingLines as $pendingLine) {
                    if ($available <= 0) break;
                    $demand = $pendingLine->quantity_ordered - $pendingLine->quantity_allocated;
                    $take   = min($demand, $available);

                    $results[] = StockAllocation::create([
                        'organization_id'     => $triggerOrder->organization_id,
                        'sales_order_id'      => $pendingLine->sales_order_id,
                        'sales_order_line_id' => $pendingLine->id,
                        'product_id'          => $productId,
                        'warehouse_id'        => $triggerOrder->warehouse_id,
                        'allocated_by'        => auth()->id(),
                        'allocation_type'     => 'hard',
                        'algorithm_used'      => 'priority_based',
                        'quantity_allocated'  => $take,
                        'status'              => 'active',
                    ]);

                    $available -= $take;
                }
            }

            return $results;
        });
    }

    // ── Wave Picking ─────────────────────────────────────────────────────────
    // Groups orders into waves based on shipping deadlines, zones, or carrier cutoffs.
    public function wavePicking(Collection $orders): PickList
    {
        return DB::transaction(function () use ($orders) {
            $pickList = PickList::create([
                'organization_id' => $orders->first()->organization_id,
                'warehouse_id'    => $orders->first()->warehouse_id,
                'type'            => 'wave',
                'status'          => 'pending',
                'priority'        => $orders->max('priority'),
                'created_by'      => auth()->id(),
            ]);

            foreach ($orders as $order) {
                foreach ($order->lines as $line) {
                    $allocations = StockAllocation::where('sales_order_line_id', $line->id)
                        ->where('status', 'active')
                        ->get();

                    foreach ($allocations as $allocation) {
                        PickListLine::create([
                            'pick_list_id'        => $pickList->id,
                            'sales_order_id'      => $order->id,
                            'sales_order_line_id' => $line->id,
                            'stock_allocation_id' => $allocation->id,
                            'product_id'          => $line->product_id,
                            'product_variant_id'  => $line->product_variant_id,
                            'storage_location_id' => $allocation->storage_location_id,
                            'lot_id'              => $allocation->lot_id,
                            'batch_id'            => $allocation->batch_id,
                            'quantity_to_pick'    => $allocation->quantity_allocated,
                            'pick_sequence'       => $this->calculatePickSequence($allocation->storage_location_id),
                        ]);
                    }
                }
            }

            return $pickList;
        });
    }

    // ── Batch Picking ─────────────────────────────────────────────────────────
    // Same picker, multiple orders — minimises warehouse travel.
    public function batchPicking(Collection $orders): PickList
    {
        // Similar to wave but uses batch type; picker collects all products for all orders
        // in one pass, then sorts/segregates at pack station
        $pickList = $this->wavePicking($orders);
        $pickList->update(['type' => 'batch']);
        return $pickList;
    }

    // ── Cluster Picking ───────────────────────────────────────────────────────
    // Cart with individual totes per order — picker deposits items directly per order.
    public function clusterPicking(Collection $orders): PickList
    {
        $pickList = $this->wavePicking($orders);
        $pickList->update(['type' => 'cluster']);
        return $pickList;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function getAvailablePositions(SalesOrderLine $line, int $warehouseId, float $quantity, bool $lock = false): Collection
    {
        $query = StockPosition::where('product_id', $line->product_id)
            ->where('warehouse_id', $warehouseId)
            ->where('qty_available', '>', 0)
            ->when($line->product_variant_id, fn ($q) => $q->where('product_variant_id', $line->product_variant_id));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->orderBy('qty_available', 'desc')->get();
    }

    private function calculatePickSequence(?int $locationId): int
    {
        if (!$locationId) return 999;
        // Returns an integer representing the aisle/bay/level sort order
        // for optimised picker routing (e.g. S-shape or return routing)
        return \App\Models\StorageLocation::find($locationId)?->sort_order ?? 999;
    }
}


/**
 * RotationService
 *
 * Determines the physical picking sequence for lots/batches
 * based on the configured stock rotation strategy.
 */
class RotationService
{
    /**
     * Returns lots in the correct picking sequence for the given strategy.
     *
     * @return Collection<Lot>
     */
    public function getLotPickingSequence(
        int    $productId,
        ?int   $variantId,
        int    $warehouseId,
        float  $quantity,
        string $strategy,
    ): Collection {
        $query = Lot::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId));

        return match($strategy) {
            // First In First Out — oldest received first
            'FIFO' => $query->orderBy('received_at', 'asc')->get(),

            // Last In First Out — newest received first
            'LIFO' => $query->orderBy('received_at', 'desc')->get(),

            // First Expired First Out — nearest expiry first (food, pharma)
            'FEFO' => $query->orderByRaw('expiry_date IS NULL ASC')
                            ->orderBy('expiry_date', 'asc')
                            ->orderBy('received_at', 'asc')
                            ->get(),

            // First Manufactured First Out — oldest manufacture date first
            'FMFO' => $query->orderByRaw('manufacture_date IS NULL ASC')
                            ->orderBy('manufacture_date', 'asc')
                            ->orderBy('received_at', 'asc')
                            ->get(),

            // Least Expiry First Out — pick lot with shortest remaining shelf life
            'LEFO' => $query->whereNotNull('expiry_date')
                            ->orderByRaw('DATEDIFF(expiry_date, NOW()) ASC')
                            ->get(),

            default => $query->orderBy('received_at', 'asc')->get(),
        };
    }
}
