<?php

namespace App\Services;

use App\DTOs\StockAdjustmentDTO;
use App\Events\InventoryUpdated;
use App\Events\LowStockDetected;
use App\Events\StockReleased;
use App\Events\StockReserved;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function list(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->inventoryRepository->allForTenant($tenantId, $filters, $perPage);
    }

    public function findOrFail(int $id, int $tenantId): InventoryItem
    {
        $item = $this->inventoryRepository->findById($id, $tenantId);

        if (! $item) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "InventoryItem [{$id}] not found for tenant [{$tenantId}]."
            );
        }

        return $item;
    }

    public function create(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data): InventoryItem {
            $item = $this->inventoryRepository->create($data);

            // Audit the initial stock entry
            $this->logTransaction($item, [
                'type'            => InventoryTransaction::TYPE_SET,
                'quantity_before' => 0,
                'quantity_change' => $item->quantity,
                'quantity_after'  => $item->quantity,
                'reserved_before' => 0,
                'reserved_change' => 0,
                'reserved_after'  => 0,
                'reason'          => 'Initial stock entry',
                'performed_by'    => $data['performed_by'] ?? null,
            ]);

            event(new InventoryUpdated($item, ['created' => true]));

            $this->checkAndFireLowStock($item);

            return $item;
        });
    }

    public function update(InventoryItem $item, array $data): InventoryItem
    {
        return DB::transaction(function () use ($item, $data): InventoryItem {
            $changes = array_keys(array_diff_assoc(
                array_intersect_key($data, $item->getAttributes()),
                $item->getAttributes()
            ));

            $updated = $this->inventoryRepository->update($item, $data);

            event(new InventoryUpdated($updated, $changes));

            return $updated;
        });
    }

    public function delete(InventoryItem $item): bool
    {
        return $this->inventoryRepository->delete($item);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Adjust stock by add / subtract / set — fully ACID, with audit trail.
     */
    public function adjustStock(InventoryItem $item, StockAdjustmentDTO $dto): InventoryItem
    {
        return DB::transaction(function () use ($item, $dto): InventoryItem {
            // Re-read with a pessimistic write lock to prevent race conditions
            $locked = InventoryItem::lockForUpdate()->findOrFail($item->id);

            $quantityBefore = $locked->quantity;

            $quantityAfter = match (true) {
                $dto->isAdd()      => $quantityBefore + $dto->quantity,
                $dto->isSubtract() => $quantityBefore - $dto->quantity,
                $dto->isSet()      => $dto->quantity,
            };

            if ($quantityAfter < 0) {
                throw new InsufficientStockException(
                    available:       $locked->available_quantity,
                    requested:       $dto->quantity,
                    inventoryItemId: $locked->id,
                );
            }

            // Persist the quantity change
            $updated = $this->inventoryRepository->setQuantity($locked, $quantityAfter);

            // Write the audit transaction
            $this->logTransaction($updated, [
                'type'            => $dto->type,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityAfter - $quantityBefore,
                'quantity_after'  => $quantityAfter,
                'reserved_before' => $updated->reserved_quantity,
                'reserved_change' => 0,
                'reserved_after'  => $updated->reserved_quantity,
                'reason'          => $dto->reason,
                'reference_type'  => $dto->referenceType,
                'reference_id'    => $dto->referenceId,
                'performed_by'    => $dto->performedBy,
                'metadata'        => $dto->metadata,
            ]);

            event(new InventoryUpdated($updated, [
                'quantity_before' => $quantityBefore,
                'quantity_after'  => $quantityAfter,
                'adjustment_type' => $dto->type,
            ], $dto->performedBy));

            $this->checkAndFireLowStock($updated);

            return $updated;
        });
    }

    /**
     * Reserve stock for an order — atomically increments reserved_quantity.
     * Does NOT reduce actual quantity; that happens when order is fulfilled.
     */
    public function reserveStock(InventoryItem $item, int $quantity, string $reason, ?string $referenceType = null, ?string $referenceId = null, ?int $performedBy = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $reason, $referenceType, $referenceId, $performedBy): InventoryItem {
            $locked = InventoryItem::lockForUpdate()->findOrFail($item->id);

            if ($locked->available_quantity < $quantity) {
                throw new InsufficientStockException(
                    available:       $locked->available_quantity,
                    requested:       $quantity,
                    inventoryItemId: $locked->id,
                );
            }

            $reservedBefore = $locked->reserved_quantity;
            $updated        = $this->inventoryRepository->incrementReserved($locked, $quantity);

            $this->logTransaction($updated, [
                'type'            => InventoryTransaction::TYPE_RESERVE,
                'quantity_before' => $updated->quantity,
                'quantity_change' => 0,
                'quantity_after'  => $updated->quantity,
                'reserved_before' => $reservedBefore,
                'reserved_change' => $quantity,
                'reserved_after'  => $updated->reserved_quantity,
                'reason'          => $reason,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'performed_by'    => $performedBy,
            ]);

            event(new StockReserved($updated, $quantity, $reason, $referenceType, $referenceId, $performedBy));

            $this->checkAndFireLowStock($updated);

            return $updated;
        });
    }

    /**
     * Release previously reserved stock — atomically decrements reserved_quantity.
     */
    public function releaseStock(InventoryItem $item, int $quantity, string $reason, ?string $referenceType = null, ?string $referenceId = null, ?int $performedBy = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $reason, $referenceType, $referenceId, $performedBy): InventoryItem {
            $locked = InventoryItem::lockForUpdate()->findOrFail($item->id);

            $releaseAmount  = min($quantity, $locked->reserved_quantity);
            $reservedBefore = $locked->reserved_quantity;

            $updated = $this->inventoryRepository->decrementReserved($locked, $releaseAmount);

            $this->logTransaction($updated, [
                'type'            => InventoryTransaction::TYPE_RELEASE,
                'quantity_before' => $updated->quantity,
                'quantity_change' => 0,
                'quantity_after'  => $updated->quantity,
                'reserved_before' => $reservedBefore,
                'reserved_change' => -$releaseAmount,
                'reserved_after'  => $updated->reserved_quantity,
                'reason'          => $reason,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'performed_by'    => $performedBy,
            ]);

            event(new StockReleased($updated, $releaseAmount, $reason, $referenceType, $referenceId, $performedBy));

            return $updated;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Saga Compensation
    |--------------------------------------------------------------------------
    */

    /**
     * Compensating transaction: undo an inventory creation.
     * Called by CompensateInventoryCreation job when a downstream saga step fails.
     */
    public function compensateCreation(int $inventoryItemId, int $tenantId, string $sagaId): bool
    {
        $item = $this->inventoryRepository->findById($inventoryItemId, $tenantId);

        if (! $item) {
            Log::warning('Compensation: InventoryItem not found', [
                'inventory_item_id' => $inventoryItemId,
                'tenant_id'         => $tenantId,
                'saga_id'           => $sagaId,
            ]);

            return false;
        }

        $deleted = $this->inventoryRepository->delete($item);

        Log::info('Saga compensation: InventoryItem soft-deleted', [
            'inventory_item_id' => $inventoryItemId,
            'tenant_id'         => $tenantId,
            'saga_id'           => $sagaId,
        ]);

        return $deleted;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function logTransaction(InventoryItem $item, array $data): InventoryTransaction
    {
        return InventoryTransaction::create(array_merge([
            'tenant_id'         => $item->tenant_id,
            'inventory_item_id' => $item->id,
        ], $data));
    }

    private function checkAndFireLowStock(InventoryItem $item): void
    {
        if ($item->is_low_stock) {
            event(new LowStockDetected($item, $item->available_quantity, $item->reorder_point));
        }
    }
}
