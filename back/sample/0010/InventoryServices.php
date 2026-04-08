<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;
use Modules\Inventory\Domain\ValueObjects\StockRotationStrategy;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;

// ═══════════════════════════════════════════════════════════════════
// RotationService
// Determines physical picking order for lots based on strategy
// ═══════════════════════════════════════════════════════════════════
final class RotationService
{
    /**
     * Returns lot rows in picking sequence for the given strategy.
     * Used by issueStock() to determine which lot to pull first.
     */
    public function getLotPickingSequence(
        int    $productId,
        ?int   $variantId,
        int    $warehouseId,
        float  $quantity,
        string $strategy,
    ): \Illuminate\Support\Collection {
        $query = DB::table('lots')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->where('available_quantity', '>', 0)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId));

        return match ($strategy) {
            StockRotationStrategy::FIFO => $query
                ->orderBy('received_at', 'asc')
                ->get(),

            StockRotationStrategy::LIFO => $query
                ->orderBy('received_at', 'desc')
                ->get(),

            StockRotationStrategy::FEFO => $query
                ->orderByRaw('expiry_date IS NULL ASC')
                ->orderBy('expiry_date', 'asc')
                ->orderBy('received_at', 'asc')
                ->get(),

            StockRotationStrategy::FMFO => $query
                ->orderByRaw('manufacture_date IS NULL ASC')
                ->orderBy('manufacture_date', 'asc')
                ->orderBy('received_at', 'asc')
                ->get(),

            StockRotationStrategy::LEFO => $query
                ->whereNotNull('expiry_date')
                ->orderByRaw('DATEDIFF(expiry_date, NOW()) ASC')
                ->get(),

            default => $query->orderBy('received_at', 'asc')->get(),
        };
    }
}


// ═══════════════════════════════════════════════════════════════════
// AllocationService
// 8 allocation algorithms — all recorded in stock_allocations.algorithm_used
// ═══════════════════════════════════════════════════════════════════
final class AllocationService
{
    public function allocate(int $salesOrderId, string $algorithm): array
    {
        return match ($algorithm) {
            AllocationAlgorithm::STRICT_RESERVATION => $this->strictReservation($salesOrderId),
            AllocationAlgorithm::SOFT_RESERVATION   => $this->softReservation($salesOrderId),
            AllocationAlgorithm::FAIR_SHARE         => $this->fairShare($salesOrderId),
            AllocationAlgorithm::PRIORITY_BASED     => $this->priorityBased($salesOrderId),
            AllocationAlgorithm::WAVE_PICKING       => $this->wavePicking([$salesOrderId]),
            AllocationAlgorithm::ZONE_PICKING       => $this->zonePicking($salesOrderId),
            AllocationAlgorithm::BATCH_PICKING      => $this->batchPicking([$salesOrderId]),
            AllocationAlgorithm::CLUSTER_PICKING    => $this->clusterPicking([$salesOrderId]),
            default                                  => $this->softReservation($salesOrderId),
        };
    }

    // ── strict_reservation — hard lock to specific lot + location ────────────
    public function strictReservation(int $salesOrderId): array
    {
        return DB::transaction(function () use ($salesOrderId) {
            $order = DB::table('sales_orders')->where('id', $salesOrderId)->first();
            $lines = DB::table('sales_order_lines')->where('sales_order_id', $salesOrderId)->get();
            $allocations = [];

            foreach ($lines as $line) {
                $remaining = (float) $line->quantity_ordered - (float) $line->quantity_allocated;
                if ($remaining <= 0) continue;

                $positions = DB::table('stock_positions')
                    ->where('product_id', $line->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where('qty_available', '>', 0)
                    ->when($line->product_variant_id, fn ($q) => $q->where('product_variant_id', $line->product_variant_id))
                    ->orderBy('qty_available', 'desc')
                    ->lockForUpdate()
                    ->get();

                foreach ($positions as $position) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, (float) $position->qty_available);

                    $allocId = DB::table('stock_allocations')->insertGetId([
                        'tenant_id'           => $order->tenant_id,
                        'sales_order_id'      => $salesOrderId,
                        'sales_order_line_id' => $line->id,
                        'product_id'          => $line->product_id,
                        'product_variant_id'  => $line->product_variant_id,
                        'warehouse_id'        => $order->warehouse_id,
                        'storage_location_id' => $position->storage_location_id,
                        'lot_id'              => $position->lot_id,
                        'batch_id'            => $position->batch_id,
                        'uom_id'              => $line->uom_id,
                        'allocated_by'        => auth()->id() ?? 1,
                        'allocation_type'     => 'hard',
                        'algorithm_used'      => AllocationAlgorithm::STRICT_RESERVATION,
                        'quantity_allocated'  => $take,
                        'quantity_fulfilled'  => 0,
                        'status'              => 'active',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);

                    DB::table('stock_positions')->where('id', $position->id)
                        ->decrement('qty_available', $take);
                    DB::table('stock_positions')->where('id', $position->id)
                        ->increment('qty_reserved', $take);
                    DB::table('sales_order_lines')->where('id', $line->id)
                        ->increment('quantity_allocated', $take);

                    $allocations[] = $allocId;
                    $remaining -= $take;
                }
            }
            return $allocations;
        });
    }

    // ── soft_reservation — claim quantity pool; resolve location at pick ─────
    public function softReservation(int $salesOrderId, int $ttlMinutes = 60): array
    {
        return DB::transaction(function () use ($salesOrderId, $ttlMinutes) {
            $order = DB::table('sales_orders')->where('id', $salesOrderId)->first();
            $lines = DB::table('sales_order_lines')->where('sales_order_id', $salesOrderId)->get();
            $allocations = [];

            foreach ($lines as $line) {
                $remaining = (float) $line->quantity_ordered - (float) $line->quantity_allocated;
                if ($remaining <= 0) continue;

                $available = (float) DB::table('stock_positions')
                    ->where('product_id', $line->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->sum('qty_available');

                $take = min($remaining, $available);
                if ($take <= 0) continue;

                // Pool-level decrement (not location-specific)
                DB::table('stock_positions')
                    ->where('product_id', $line->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->update(['qty_available' => DB::raw("GREATEST(qty_available - {$take}, 0)")]);

                $allocId = DB::table('stock_allocations')->insertGetId([
                    'tenant_id'           => $order->tenant_id,
                    'sales_order_id'      => $salesOrderId,
                    'sales_order_line_id' => $line->id,
                    'product_id'          => $line->product_id,
                    'product_variant_id'  => $line->product_variant_id,
                    'warehouse_id'        => $order->warehouse_id,
                    'allocated_by'        => auth()->id() ?? 1,
                    'allocation_type'     => 'soft',
                    'algorithm_used'      => AllocationAlgorithm::SOFT_RESERVATION,
                    'quantity_allocated'  => $take,
                    'quantity_fulfilled'  => 0,
                    'status'              => 'active',
                    'expires_at'          => now()->addMinutes($ttlMinutes),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                DB::table('sales_order_lines')->where('id', $line->id)
                    ->increment('quantity_allocated', $take);

                $allocations[] = $allocId;
            }
            return $allocations;
        });
    }

    // ── fair_share — proportional split of scarce stock across all orders ────
    public function fairShare(int $triggerOrderId): array
    {
        return DB::transaction(function () use ($triggerOrderId) {
            $order   = DB::table('sales_orders')->where('id', $triggerOrderId)->first();
            $results = [];

            $productIds = DB::table('sales_order_lines')
                ->where('sales_order_id', $triggerOrderId)
                ->pluck('product_id');

            foreach ($productIds as $productId) {
                $available = (float) DB::table('stock_positions')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->sum('qty_available');

                // All open lines across all confirmed orders needing this product
                $pending = DB::table('sales_order_lines as sol')
                    ->join('sales_orders as so', 'so.id', '=', 'sol.sales_order_id')
                    ->where('sol.product_id', $productId)
                    ->where('so.warehouse_id', $order->warehouse_id)
                    ->whereIn('so.status', ['confirmed', 'picking'])
                    ->whereRaw('sol.quantity_ordered > sol.quantity_allocated')
                    ->select('sol.*', 'so.tenant_id')
                    ->get();

                $totalDemand = $pending->sum(fn ($l) => $l->quantity_ordered - $l->quantity_allocated);
                if ($totalDemand <= 0) continue;

                $ratio = min(1.0, $available / $totalDemand);

                foreach ($pending as $pendingLine) {
                    $demand = $pendingLine->quantity_ordered - $pendingLine->quantity_allocated;
                    $share  = floor($demand * $ratio * 10000) / 10000;
                    if ($share <= 0) continue;

                    $results[] = DB::table('stock_allocations')->insertGetId([
                        'tenant_id'           => $pendingLine->tenant_id,
                        'sales_order_id'      => $pendingLine->sales_order_id,
                        'sales_order_line_id' => $pendingLine->id,
                        'product_id'          => $productId,
                        'warehouse_id'        => $order->warehouse_id,
                        'allocated_by'        => auth()->id() ?? 1,
                        'allocation_type'     => 'soft',
                        'algorithm_used'      => AllocationAlgorithm::FAIR_SHARE,
                        'quantity_allocated'  => $share,
                        'quantity_fulfilled'  => 0,
                        'status'              => 'active',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
            }
            return $results;
        });
    }

    // ── priority_based — highest priority (field + date) gets first ──────────
    public function priorityBased(int $triggerOrderId): array
    {
        return DB::transaction(function () use ($triggerOrderId) {
            $order   = DB::table('sales_orders')->where('id', $triggerOrderId)->first();
            $results = [];

            $productIds = DB::table('sales_order_lines')
                ->where('sales_order_id', $triggerOrderId)
                ->pluck('product_id');

            foreach ($productIds as $productId) {
                $available = (float) DB::table('stock_positions')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->sum('qty_available');

                $pending = DB::table('sales_order_lines as sol')
                    ->join('sales_orders as so', 'so.id', '=', 'sol.sales_order_id')
                    ->where('sol.product_id', $productId)
                    ->where('so.warehouse_id', $order->warehouse_id)
                    ->whereIn('so.status', ['confirmed', 'picking'])
                    ->whereRaw('sol.quantity_ordered > sol.quantity_allocated')
                    ->orderByDesc('so.priority')
                    ->orderBy('so.order_date')
                    ->select('sol.*', 'so.tenant_id')
                    ->get();

                foreach ($pending as $pendingLine) {
                    if ($available <= 0) break;
                    $demand = $pendingLine->quantity_ordered - $pendingLine->quantity_allocated;
                    $take   = min($demand, $available);

                    $results[] = DB::table('stock_allocations')->insertGetId([
                        'tenant_id'           => $pendingLine->tenant_id,
                        'sales_order_id'      => $pendingLine->sales_order_id,
                        'sales_order_line_id' => $pendingLine->id,
                        'product_id'          => $productId,
                        'warehouse_id'        => $order->warehouse_id,
                        'allocated_by'        => auth()->id() ?? 1,
                        'allocation_type'     => 'hard',
                        'algorithm_used'      => AllocationAlgorithm::PRIORITY_BASED,
                        'quantity_allocated'  => $take,
                        'quantity_fulfilled'  => 0,
                        'status'              => 'active',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                    $available -= $take;
                }
            }
            return $results;
        });
    }

    // ── wave_picking — group orders by deadline/carrier cutoff into waves ────
    public function wavePicking(array $orderIds): int
    {
        return DB::transaction(function () use ($orderIds) {
            $firstOrder = DB::table('sales_orders')->whereIn('id', $orderIds)->first();

            $pickListId = DB::table('pick_lists')->insertGetId([
                'tenant_id'    => $firstOrder->tenant_id,
                'warehouse_id' => $firstOrder->warehouse_id,
                'type'         => 'wave',
                'status'       => 'pending',
                'priority'     => DB::table('sales_orders')->whereIn('id', $orderIds)->max('priority') ?? 0,
                'created_by'   => auth()->id() ?? 1,
                'pick_number'  => 'PCK-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $this->buildPickListLines($pickListId, $orderIds, 'wave');
            return $pickListId;
        });
    }

    // ── zone_picking — assign zones to individual pickers ────────────────────
    public function zonePicking(int $salesOrderId): int
    {
        return $this->wavePicking([$salesOrderId]);
    }

    // ── batch_picking — single picker, multiple orders, one pass ─────────────
    public function batchPicking(array $orderIds): int
    {
        $listId = $this->wavePicking($orderIds);
        DB::table('pick_lists')->where('id', $listId)->update(['type' => 'batch']);
        return $listId;
    }

    // ── cluster_picking — cart with individual totes per order ───────────────
    public function clusterPicking(array $orderIds): int
    {
        $listId = $this->wavePicking($orderIds);
        DB::table('pick_lists')->where('id', $listId)->update(['type' => 'cluster']);
        return $listId;
    }

    // ── Release expired soft reservations ────────────────────────────────────
    public function releaseExpiredReservations(): int
    {
        $expired = DB::table('stock_allocations')
            ->where('allocation_type', 'soft')
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $alloc) {
            DB::table('stock_positions')
                ->where('product_id', $alloc->product_id)
                ->where('warehouse_id', $alloc->warehouse_id)
                ->update([
                    'qty_available' => DB::raw("qty_available + {$alloc->quantity_allocated}"),
                    'qty_reserved'  => DB::raw("GREATEST(qty_reserved - {$alloc->quantity_allocated}, 0)"),
                ]);
            DB::table('stock_allocations')->where('id', $alloc->id)->update(['status' => 'expired']);
        }

        return $expired->count();
    }

    private function buildPickListLines(int $pickListId, array $orderIds, string $type): void
    {
        $allocations = DB::table('stock_allocations')
            ->whereIn('sales_order_id', $orderIds)
            ->where('status', 'active')
            ->get();

        foreach ($allocations as $alloc) {
            $line = DB::table('sales_order_lines')->where('id', $alloc->sales_order_line_id)->first();
            if (!$line) continue;

            // Optimised pick sequence: sort by location sort_order
            $sequence = DB::table('storage_locations')
                ->where('id', $alloc->storage_location_id)
                ->value('sort_order') ?? 999;

            DB::table('pick_list_lines')->insert([
                'pick_list_id'        => $pickListId,
                'sales_order_id'      => $alloc->sales_order_id,
                'sales_order_line_id' => $alloc->sales_order_line_id,
                'stock_allocation_id' => $alloc->id,
                'product_id'          => $alloc->product_id,
                'product_variant_id'  => $alloc->product_variant_id,
                'storage_location_id' => $alloc->storage_location_id,
                'lot_id'              => $alloc->lot_id,
                'batch_id'            => $alloc->batch_id,
                'serial_number_id'    => $alloc->serial_number_id,
                'quantity_to_pick'    => $alloc->quantity_allocated,
                'quantity_picked'     => 0,
                'status'              => 'pending',
                'pick_sequence'       => $sequence,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }
}


// ═══════════════════════════════════════════════════════════════════
// LedgerService — append-only immutable journal writer
// ═══════════════════════════════════════════════════════════════════
final class LedgerService
{
    public function write(array $params, array $position, string $method, string $direction): array
    {
        $qty    = $params['quantity'];
        $before = ($position['qty_on_hand'] ?? 0) + ($direction === 'IN' ? -$qty : $qty);
        $after  = $position['qty_on_hand'] ?? 0;

        $ref = $this->nextReference((int) $params['tenant_id']);

        $id = DB::table('stock_ledger_entries')->insertGetId([
            'tenant_id'             => $params['tenant_id'],
            'reference_number'      => $ref,
            'product_id'            => $params['product_id'],
            'product_variant_id'    => $params['product_variant_id'] ?? null,
            'warehouse_id'          => $params['warehouse_id'],
            'storage_location_id'   => $params['storage_location_id'] ?? null,
            'lot_id'                => $params['lot_id'] ?? null,
            'batch_id'              => $params['batch_id'] ?? null,
            'serial_number_id'      => $params['serial_number_id'] ?? null,
            'uom_id'                => $params['uom_id'] ?? null,
            'movement_type'         => $params['movement_type'],
            'direction'             => $direction,
            'quantity'              => $qty,
            'quantity_before'       => $before,
            'quantity_after'        => $after,
            'valuation_method'      => $method,
            'unit_cost'             => $params['unit_cost'] ?? 0,
            'total_cost'            => $params['total_cost'] ?? (($params['unit_cost'] ?? 0) * $qty),
            'average_cost_before'   => $params['average_cost_before'] ?? null,
            'average_cost_after'    => $params['average_cost_after'] ?? null,
            'source_document_type'  => $params['source_document_type'] ?? null,
            'source_document_id'    => $params['source_document_id'] ?? null,
            'source_document_number'=> $params['source_document_number'] ?? null,
            'source_line_id'        => $params['source_line_id'] ?? null,
            'reason_code'           => $params['reason_code'] ?? null,
            'notes'                 => $params['notes'] ?? null,
            'created_by'            => auth()->id() ?? 1,
            'movement_date'         => $params['movement_date'] ?? now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        return array_merge($params, ['id' => $id, 'reference_number' => $ref, 'direction' => $direction]);
    }

    private function nextReference(int $tenantId): string
    {
        return DB::transaction(function () use ($tenantId) {
            $seq = DB::table('document_sequences')
                ->where('tenant_id', $tenantId)
                ->where('document_type', 'journal')
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::table('document_sequences')->insert([
                    'tenant_id'     => $tenantId,
                    'document_type' => 'journal',
                    'prefix'        => 'JRN',
                    'next_number'   => 1,
                    'padding'       => 6,
                    'separator'     => '-',
                    'include_year'  => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $seq = DB::table('document_sequences')
                    ->where('tenant_id', $tenantId)
                    ->where('document_type', 'journal')
                    ->lockForUpdate()
                    ->first();
            }

            $number = str_pad($seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
            $ref    = "{$seq->prefix}-" . now()->year . "-{$number}";

            DB::table('document_sequences')
                ->where('id', $seq->id)
                ->increment('next_number');

            return $ref;
        });
    }
}


// ═══════════════════════════════════════════════════════════════════
// SettingsResolver
// Resolves per-tenant → per-org → per-warehouse → per-product
// settings hierarchy for valuation, rotation, and allocation
// ═══════════════════════════════════════════════════════════════════
final class SettingsResolver
{
    private array $cache = [];

    public function resolveValuationMethod(
        int    $tenantId,
        int    $productId,
        int    $warehouseId,
        ?string $override = null,
    ): string {
        if ($override) return $override;

        // Product-level override
        $product = DB::table('products')->where('id', $productId)->first();
        if ($product?->valuation_method) return $product->valuation_method;

        // Product valuation override table
        $pvo = DB::table('product_valuation_overrides')
            ->where('product_id', $productId)
            ->where(fn ($q) => $q->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id'))
            ->where('is_active', true)
            ->orderByDesc('warehouse_id') // prefer warehouse-specific over global
            ->first();
        if ($pvo?->valuation_method) return $pvo->valuation_method;

        // Warehouse-level override
        $warehouse = DB::table('warehouses')->where('id', $warehouseId)->first();
        if ($warehouse?->valuation_method) return $warehouse->valuation_method;

        // Tenant default
        return $this->getTenantSettings($tenantId)['default_valuation_method'] ?? ValuationMethod::AVCO;
    }

    public function resolveRotationStrategy(
        int    $tenantId,
        int    $productId,
        int    $warehouseId,
        ?string $override = null,
    ): string {
        if ($override) return $override;

        $warehouse = DB::table('warehouses')->where('id', $warehouseId)->first();
        if ($warehouse?->stock_rotation) return $warehouse->stock_rotation;

        return $this->getTenantSettings($tenantId)['default_stock_rotation'] ?? StockRotationStrategy::FIFO;
    }

    public function resolveAllocationAlgorithm(
        int    $tenantId,
        int    $warehouseId,
        ?string $override = null,
    ): string {
        if ($override) return $override;

        $warehouse = DB::table('warehouses')->where('id', $warehouseId)->first();
        if ($warehouse?->allocation_algorithm) return $warehouse->allocation_algorithm;

        return $this->getTenantSettings($tenantId)['default_allocation_algorithm'] ?? AllocationAlgorithm::SOFT_RESERVATION;
    }

    public function getTenantSettings(int $tenantId): array
    {
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $settings = DB::table('inventory_settings')
            ->where('tenant_id', $tenantId)
            ->whereNull('organization_id')
            ->first();

        return $this->cache[$tenantId] = $settings ? (array) $settings : [
            'default_valuation_method'     => ValuationMethod::AVCO,
            'default_stock_rotation'       => StockRotationStrategy::FIFO,
            'default_allocation_algorithm' => AllocationAlgorithm::SOFT_RESERVATION,
            'allow_negative_stock'         => false,
            'warn_on_negative_stock'       => true,
            'batch_tracking_enabled'       => true,
            'lot_tracking_enabled'         => true,
            'serial_tracking_enabled'      => true,
            'expiry_tracking_enabled'      => true,
            'expiry_warning_days'          => 30,
        ];
    }
}


// ═══════════════════════════════════════════════════════════════════
// AlertService — real-time stock alert evaluation
// ═══════════════════════════════════════════════════════════════════
final class AlertService
{
    public function evaluate(int $tenantId, int $productId, int $warehouseId): void
    {
        $settings = app(SettingsResolver::class)->getTenantSettings($tenantId);
        $product  = DB::table('products')->where('id', $productId)->first();
        if (!$product) return;

        $available = (float) DB::table('stock_positions')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('qty_available');

        // Out of stock
        if ($available <= 0) {
            $this->upsert($tenantId, $productId, $warehouseId, 'out_of_stock', $available, 0);
        }
        // Low stock
        elseif ($product->reorder_point !== null && $available <= (float) $product->reorder_point) {
            $this->upsert($tenantId, $productId, $warehouseId, 'low_stock', $available, (float) $product->reorder_point);
        }
        // Overstock
        elseif ($product->max_stock_level !== null) {
            $onHand = (float) DB::table('stock_positions')
                ->where('product_id', $productId)->where('warehouse_id', $warehouseId)->sum('qty_on_hand');
            if ($onHand >= (float) $product->max_stock_level) {
                $this->upsert($tenantId, $productId, $warehouseId, 'overstock', $onHand, (float) $product->max_stock_level);
            }
        } else {
            // Resolve any existing alert
            DB::table('stock_alerts')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->whereIn('alert_type', ['low_stock', 'out_of_stock'])
                ->where('status', 'active')
                ->update(['status' => 'resolved', 'resolved_at' => now()]);
        }

        // Negative stock warning
        if ($available < 0 && $settings['warn_on_negative_stock']) {
            $this->upsert($tenantId, $productId, $warehouseId, 'negative_stock', $available, 0);
        }
    }

    public function scanExpiry(int $tenantId): void
    {
        $settings    = app(SettingsResolver::class)->getTenantSettings($tenantId);
        $warningDays = $settings['expiry_warning_days'] ?? 30;
        $cutoff      = now()->addDays($warningDays);

        // Expiring soon
        DB::table('lots')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['available', 'reserved'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $cutoff)
            ->where('expiry_date', '>', now())
            ->where('available_quantity', '>', 0)
            ->each(function ($lot) use ($tenantId) {
                $this->upsert($tenantId, $lot->product_id, $lot->warehouse_id, 'expiring_soon',
                    $lot->available_quantity, null, $lot->expiry_date);
            });

        // Expired — also update lot status
        DB::table('lots')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['available', 'reserved'])
            ->where('expiry_date', '<=', now())
            ->where('available_quantity', '>', 0)
            ->each(function ($lot) use ($tenantId) {
                $this->upsert($tenantId, $lot->product_id, $lot->warehouse_id, 'expired',
                    $lot->available_quantity, null, $lot->expiry_date);
                DB::table('lots')->where('id', $lot->id)->update(['status' => 'expired']);
            });
    }

    private function upsert(
        int    $tenantId,
        int    $productId,
        int    $warehouseId,
        string $alertType,
        ?float $currentQty,
        ?float $thresholdQty,
        mixed  $expiryDate = null,
    ): void {
        DB::table('stock_alerts')->updateOrInsert(
            [
                'tenant_id'          => $tenantId,
                'product_id'         => $productId,
                'warehouse_id'       => $warehouseId,
                'alert_type'         => $alertType,
                'status'             => 'active',
            ],
            [
                'current_quantity'   => $currentQty,
                'threshold_quantity' => $thresholdQty,
                'expiry_date'        => $expiryDate,
                'updated_at'         => now(),
                'created_at'         => now(),
            ]
        );
    }
}
