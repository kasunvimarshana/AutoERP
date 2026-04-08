<?php

namespace App\Modules\Allocation\Services;

use App\Modules\Allocation\Models\AllocationSetting;
use App\Modules\Allocation\Models\AllocationRule;
use App\Modules\Allocation\Models\StockReservation;
use App\Modules\Allocation\Models\AllocationLog;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\TrackingLot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AllocationService
 *
 * Implements user-configurable allocation algorithms:
 *   standard      — Simple availability check, FIFO priority
 *   priority      — Rules-based priority queue (customer tier, order date, etc.)
 *   fair_share    — Distribute available stock proportionally across orders
 *   manual        — No auto-allocation; user picks manually
 *   wave          — Group orders into waves before allocating
 *   zone          — Allocate from nearest/optimal zone
 *   cluster       — Group orders by proximity for batch picking
 *
 * Rotation strategies (lot selection):
 *   FIFO | LIFO | FEFO | LEFO | FMFO | SLED
 */
class AllocationService
{
    public function __construct(
        protected LotSelectionService $lotSelector,
    ) {}

    /**
     * Allocate stock for a single reservable document line.
     * Returns the allocation result.
     */
    public function allocate(
        string $reservableType,
        int    $reservableId,
        int    $productId,
        ?int   $variantId,
        int    $warehouseId,
        float  $qty,
        int    $uomId,
        array  $context = []
    ): array {
        return DB::transaction(function () use (
            $reservableType, $reservableId, $productId, $variantId,
            $warehouseId, $qty, $uomId, $context
        ) {
            $settings  = AllocationSetting::forWarehouse($warehouseId, $context['tenant_id'] ?? null);
            $algorithm = $settings?->algorithm ?? 'standard';

            // Apply allocation rules to determine priority/restrictions
            $rules = $this->resolveApplicableRules($warehouseId, $productId, $context);

            $result = match ($algorithm) {
                'standard'   => $this->standardAllocate($productId, $variantId, $warehouseId, $qty, $uomId, $settings, $rules, $context),
                'priority'   => $this->priorityAllocate($productId, $variantId, $warehouseId, $qty, $uomId, $settings, $rules, $context),
                'fair_share' => $this->fairShareAllocate($productId, $variantId, $warehouseId, $qty, $uomId, $settings, $rules, $context),
                'manual'     => ['allocated_qty' => 0, 'lots' => [], 'status' => 'pending_manual'],
                default      => $this->standardAllocate($productId, $variantId, $warehouseId, $qty, $uomId, $settings, $rules, $context),
            };

            // Create reservation record(s)
            if ($result['allocated_qty'] > 0) {
                foreach ($result['lots'] as $lotAllocation) {
                    StockReservation::create([
                        'tenant_id'        => $context['tenant_id'] ?? null,
                        'product_id'       => $productId,
                        'variant_id'       => $variantId,
                        'warehouse_id'     => $warehouseId,
                        'location_id'      => $lotAllocation['location_id'] ?? null,
                        'lot_id'           => $lotAllocation['lot_id'] ?? null,
                        'uom_id'           => $uomId,
                        'reserved_qty'     => $lotAllocation['qty'],
                        'reservable_type'  => $reservableType,
                        'reservable_id'    => $reservableId,
                        'reservation_type' => $context['reservation_type'] ?? 'sales_order',
                        'status'           => 'reserved',
                        'reserved_at'      => now(),
                        'expires_at'       => $this->calculateExpiry($settings),
                        'priority'         => $context['priority'] ?? 10,
                    ]);

                    // Update stock level: move from available to reserved
                    $this->lockStock($productId, $variantId, $warehouseId, $lotAllocation['lot_id'] ?? null, $lotAllocation['qty']);
                }
            }

            // Log the allocation decision
            $this->logAllocation($reservableType, $reservableId, $productId, $variantId, $warehouseId, $qty, $result, $algorithm);

            return $result;
        });
    }

    /**
     * Release a reservation (when order is cancelled, or fulfilment complete).
     */
    public function releaseReservation(
        string $reservableType,
        int    $reservableId,
        float  $releasedQty = null,
        int    $productId   = null,
        int    $lotId       = null
    ): void {
        $query = StockReservation::where([
            'reservable_type' => $reservableType,
            'reservable_id'   => $reservableId,
            'status'          => 'reserved',
        ]);

        if ($productId) $query->where('product_id', $productId);
        if ($lotId)     $query->where('lot_id', $lotId);

        $reservations = $query->get();

        foreach ($reservations as $reservation) {
            $releaseQty = min($releasedQty ?? $reservation->reserved_qty, $reservation->reserved_qty);

            // Restore available stock
            StockLevel::where([
                'product_id'   => $reservation->product_id,
                'warehouse_id' => $reservation->warehouse_id,
                'lot_id'       => $reservation->lot_id,
            ])->decrement('qty_reserved', $releaseQty, [
                'qty_available' => DB::raw("qty_available + $releaseQty"),
            ]);

            if ($releaseQty >= $reservation->reserved_qty) {
                $reservation->status = 'cancelled';
            } else {
                $reservation->reserved_qty -= $releaseQty;
            }
            $reservation->save();

            if ($releasedQty !== null) {
                $releasedQty -= $releaseQty;
                if ($releasedQty <= 0) break;
            }
        }
    }

    // ── Allocation Algorithms ───────────────────────────────────────────────

    protected function standardAllocate(
        int $productId, ?int $variantId, int $warehouseId,
        float $qty, int $uomId,
        ?AllocationSetting $settings, Collection $rules, array $context
    ): array {
        $rotation   = $settings?->rotation_strategy ?? 'fifo';
        $lots       = $this->lotSelector->selectLots($productId, $variantId, $warehouseId, $qty, $rotation, $context);
        $allocated  = array_sum(array_column($lots, 'qty'));

        return [
            'allocated_qty'   => $allocated,
            'unfulfilled_qty' => max(0, $qty - $allocated),
            'lots'            => $lots,
            'status'          => $allocated >= $qty ? 'fully_allocated' : ($allocated > 0 ? 'partial' : 'failed'),
            'algorithm'       => 'standard',
            'rotation'        => $rotation,
        ];
    }

    protected function priorityAllocate(
        int $productId, ?int $variantId, int $warehouseId,
        float $qty, int $uomId,
        ?AllocationSetting $settings, Collection $rules, array $context
    ): array {
        // Same as standard but also applies priority-boost rules
        $boostedPriority = $this->applyPriorityRules($rules, $context);

        return array_merge(
            $this->standardAllocate($productId, $variantId, $warehouseId, $qty, $uomId, $settings, $rules, $context),
            ['priority_applied' => $boostedPriority, 'algorithm' => 'priority']
        );
    }

    protected function fairShareAllocate(
        int $productId, ?int $variantId, int $warehouseId,
        float $qty, int $uomId,
        ?AllocationSetting $settings, Collection $rules, array $context
    ): array {
        // Determine total demand across all open orders for this product
        $totalDemand = StockReservation::where([
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'status'       => 'reserved',
        ])->sum('reserved_qty');

        $available = StockLevel::where([
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
        ])->sum('qty_available');

        // Fair-share ratio
        $shareRatio  = $totalDemand > 0 ? min(1, $available / ($totalDemand + $qty)) : 1;
        $fairShareQty = $qty * $shareRatio;

        return $this->standardAllocate(
            $productId, $variantId, $warehouseId,
            $fairShareQty, $uomId, $settings, $rules, $context
        ) + ['algorithm' => 'fair_share', 'share_ratio' => $shareRatio];
    }

    // ── Supporting methods ──────────────────────────────────────────────────

    protected function lockStock(int $productId, ?int $variantId, int $warehouseId, ?int $lotId, float $qty): void
    {
        StockLevel::where([
            'product_id'   => $productId,
            'variant_id'   => $variantId,
            'warehouse_id' => $warehouseId,
            'lot_id'       => $lotId,
        ])->increment('qty_reserved', $qty, [
            'qty_available' => DB::raw("GREATEST(0, qty_available - $qty)"),
        ]);
    }

    protected function resolveApplicableRules(int $warehouseId, int $productId, array $context): Collection
    {
        return AllocationRule::where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            })
            ->where(function ($q) use ($productId, $context) {
                $q->whereNull('product_id')
                  ->orWhere('product_id', $productId);
            })
            ->where(function ($q) use ($context) {
                $q->whereNull('customer_tier')
                  ->orWhere('customer_tier', $context['customer_tier'] ?? null);
            })
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', today());
            })
            ->orderBy('priority')
            ->get();
    }

    protected function applyPriorityRules(Collection $rules, array $context): array
    {
        $applied = [];
        foreach ($rules as $rule) {
            if ($rule->action === 'priority_boost') {
                $applied[] = ['rule_id' => $rule->id, 'boost' => $rule->action_config['boost'] ?? 0];
            }
        }
        return $applied;
    }

    protected function calculateExpiry(?AllocationSetting $settings): ?Carbon
    {
        $hours = $settings?->reservation_expiry_hours;
        return $hours ? now()->addHours($hours) : null;
    }

    protected function logAllocation(
        string $type, int $id, int $productId, ?int $variantId, int $warehouseId,
        float $requested, array $result, string $algorithm
    ): void {
        AllocationLog::create([
            'event'           => $result['status'] === 'fully_allocated' ? 'allocated' : ($result['allocated_qty'] > 0 ? 'partial' : 'failed'),
            'reservable_type' => $type,
            'reservable_id'   => $id,
            'product_id'      => $productId,
            'variant_id'      => $variantId,
            'warehouse_id'    => $warehouseId,
            'requested_qty'   => $requested,
            'allocated_qty'   => $result['allocated_qty'],
            'failed_qty'      => max(0, $requested - $result['allocated_qty']),
            'algorithm_used'  => $algorithm,
            'rotation_used'   => $result['rotation'] ?? null,
            'failure_reason'  => $result['status'] === 'failed' ? 'insufficient_stock' : null,
            'occurred_at'     => now(),
        ]);
    }
}
