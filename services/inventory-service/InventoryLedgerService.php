<?php

namespace Services\Inventory\Domain;

use Shared\DTO\InventoryMovementRequest;
use Shared\Contracts\InventoryRepositoryInterface;
use Shared\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Ledger Implementation
 * Ensures all movements are immutable and transactional.
 */
class InventoryLedgerService
{
    private InventoryRepositoryInterface $repository;

    public function __construct(InventoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Records a stock movement (In, Out, Transfer, Adjust).
     * Enforces strict ledger-driven updates.
     */
    public function recordMovement(InventoryMovementRequest $request): void
    {
        DB::transaction(function () use ($request) {
            // 1. Pessimistic Locking for deduction to prevent race conditions
            if ($request->quantity < 0) {
                $stock = $this->repository->lockStock(
                    $request->productId, 
                    $request->warehouseId,
                    $request->locationId,
                    $request->binId
                );

                if ($stock->available_qty < abs($request->quantity)) {
                    throw new InsufficientStockException("Insufficient stock for Product: {$request->productId}");
                }
            }

            // 2. Create Immutable Ledger Entry
            $ledgerEntry = $this->repository->createLedgerEntry([
                'tenant_id' => $request->tenantId,
                'product_id' => $request->productId,
                'warehouse_id' => $request->warehouseId,
                'location_id' => $request->locationId,
                'bin_id' => $request->binId,
                'movement_type' => $request->type, // SALE, PURCHASE, ADJUSTMENT, TRANSFER
                'quantity' => $request->quantity,
                'reference_id' => $request->referenceId, // Order ID, GRN ID, etc.
                'reference_type' => $request->referenceType,
                'batch_id' => $request->batchId,
                'serial_numbers' => $request->serialNumbers,
                'unit_cost' => $request->unitCost, // BCMath for precision
                'total_valuation' => bcmul($request->unitCost, (string)$request->quantity, 4),
                'created_at' => now(),
                'user_id' => $request->userId,
                'metadata' => $request->metadata,
            ]);

            // 3. Update Real-time Stock Snapshot (Optimistic Locking for updates)
            $this->repository->updateStockSnapshot($ledgerEntry);

            // 4. Emit Domain Event for downstream services (Finance, Reporting)
            // EventBus::publish(new StockMovedEvent($ledgerEntry));
        });
    }

    /**
     * Reconstructs stock history for audit or correction.
     */
    public function reconstructStockHistory(string $productId, ?string $warehouseId = null): array
    {
        return $this->repository->getLedgerEntries($productId, $warehouseId);
    }
}
