<?php

namespace App\Services;

use App\Contracts\Services\InventoryServiceInterface;
use App\Events\StockAdjusted;
use App\Models\StockBatch;
use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class InventoryService implements InventoryServiceInterface
{
    /** Movement types that remove stock from a warehouse. */
    private const OUTBOUND = ['shipment', 'transfer_out'];

    public function paginateStock(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockItem::where('tenant_id', $tenantId)
            ->with(['product', 'variant', 'warehouse']);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Record a stock movement and update the on-hand quantity.
     *
     * For inbound movements a new StockBatch cost layer is created (FIFO/FEFO).
     * For outbound movements the oldest batches (FIFO) or nearest-expiry batches
     * (FEFO) are depleted first.
     */
    public function adjust(
        string $tenantId,
        string $warehouseId,
        string $productId,
        string $quantity,
        string $movementType,
        ?string $variantId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $batchNumber = null,
        ?string $lotNumber = null,
        ?string $serialNumber = null,
        ?\DateTimeInterface $expiryDate = null,
        string $valuationMethod = 'fifo'
    ): StockMovement {
        return DB::transaction(function () use (
            $tenantId, $warehouseId, $productId, $quantity,
            $movementType, $variantId, $notes, $referenceType, $referenceId,
            $batchNumber, $lotNumber, $serialNumber, $expiryDate, $valuationMethod
        ) {
            // Pessimistic lock on stock item row to prevent race conditions
            $stockItem = StockItem::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $stockItem) {
                $stockItem = StockItem::create([
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity_on_hand' => '0',
                    'quantity_reserved' => '0',
                    'quantity_available' => '0',
                    'cost_per_unit' => '0',
                    'currency' => 'USD',
                ]);
            }

            $isOutbound = in_array($movementType, self::OUTBOUND, true);
            $direction = $isOutbound ? '-1' : '1';
            $newQty = bcadd($stockItem->quantity_on_hand, bcmul($quantity, $direction, 8), 8);

            if (bccomp($newQty, '0', 8) < 0) {
                throw new \RuntimeException('Insufficient stock. Available: '.$stockItem->quantity_on_hand);
            }

            // quantity_available = quantity_on_hand - quantity_reserved
            $newAvailable = bcsub($newQty, $stockItem->quantity_reserved, 8);

            $stockItem->update([
                'quantity_on_hand' => $newQty,
                'quantity_available' => $newAvailable,
            ]);

            $movement = StockMovement::create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => Auth::id(),
                'batch_number' => $batchNumber,
                'lot_number' => $lotNumber,
                'serial_number' => $serialNumber,
                'expiry_date' => $expiryDate?->format('Y-m-d'),
                'valuation_method' => $valuationMethod,
            ]);

            if ($isOutbound) {
                $this->depleteBatches(
                    $tenantId, $warehouseId, $productId, $variantId, $quantity, $valuationMethod
                );
            } else {
                // Create a new cost layer for this inbound movement
                StockBatch::create([
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'movement_id' => $movement->id,
                    'batch_number' => $batchNumber,
                    'lot_number' => $lotNumber,
                    'serial_number' => $serialNumber,
                    'expiry_date' => $expiryDate?->format('Y-m-d'),
                    'quantity_received' => $quantity,
                    'quantity_remaining' => $quantity,
                    'cost_per_unit' => $stockItem->cost_per_unit,
                    'currency' => $stockItem->currency,
                    'received_at' => now(),
                ]);
            }

            Event::dispatch(new StockAdjusted($movement));

            return $movement;
        });
    }

    /**
     * Deplete cost layers using FIFO or FEFO strategy.
     *
     * FIFO: oldest received_at first.
     * FEFO: nearest expiry_date first (null expiry treated as non-expiring, consumed last).
     */
    private function depleteBatches(
        string $tenantId,
        string $warehouseId,
        string $productId,
        ?string $variantId,
        string $quantity,
        string $method
    ): void {
        $query = StockBatch::where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('quantity_remaining', '>', 0)
            ->lockForUpdate();

        if ($method === 'fefo') {
            $query->orderByRaw('expiry_date IS NULL, expiry_date ASC')
                ->orderBy('received_at');
        } else {
            // Default: FIFO
            $query->orderBy('received_at');
        }

        $remaining = $quantity;

        foreach ($query->cursor() as $batch) {
            if (bccomp($remaining, '0', 8) <= 0) {
                break;
            }

            if (bccomp($remaining, $batch->quantity_remaining, 8) >= 0) {
                $remaining = bcsub($remaining, $batch->quantity_remaining, 8);
                $batch->quantity_remaining = '0.00000000';
            } else {
                $batch->quantity_remaining = bcsub($batch->quantity_remaining, $remaining, 8);
                $remaining = '0.00000000';
            }

            $batch->save();
        }
    }

    /**
     * Return stock items whose quantity_available is at or below their reorder_point.
     *
     * @return Collection<int, StockItem>
     */
    public function getLowStockItems(string $tenantId, ?string $warehouseId = null): Collection
    {
        $query = StockItem::where('tenant_id', $tenantId)
            ->whereRaw('quantity_available <= reorder_point')
            ->where('reorder_point', '>', 0)
            ->with(['product', 'variant', 'warehouse']);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Return stock batches expiring within $daysAhead days (inclusive).
     *
     * @return Collection<int, StockBatch>
     */
    public function getExpiringBatches(string $tenantId, int $daysAhead = 30, ?string $warehouseId = null): Collection
    {
        $query = StockBatch::where('tenant_id', $tenantId)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($daysAhead))
            ->where('quantity_remaining', '>', 0)
            ->with(['product', 'variant', 'warehouse'])
            ->orderBy('expiry_date');

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Calculate the weighted-average unit cost across all open FIFO layers.
     */
    public function getFifoCost(
        string $tenantId,
        string $warehouseId,
        string $productId,
        ?string $variantId = null
    ): string {
        $batches = StockBatch::where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('quantity_remaining', '>', 0)
            ->get(['quantity_remaining', 'cost_per_unit']);

        if ($batches->isEmpty()) {
            return '0.00000000';
        }

        $totalQty = '0';
        $totalCost = '0';

        foreach ($batches as $batch) {
            $totalQty = bcadd($totalQty, $batch->quantity_remaining, 8);
            $totalCost = bcadd(
                $totalCost,
                bcmul($batch->quantity_remaining, $batch->cost_per_unit, 8),
                8
            );
        }

        if (bccomp($totalQty, '0', 8) === 0) {
            return '0.00000000';
        }

        return bcdiv($totalCost, $totalQty, 8);
    }
}
