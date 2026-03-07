<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\InventoryDepleted;
use App\Events\InventoryLow;
use App\Events\InventoryUpdated;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Repositories\Interfaces\InventoryTransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly InventoryTransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * Return a paginated, filtered list of inventory items.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAllInventory(array $filters): LengthAwarePaginator
    {
        return $this->inventoryRepository->getAll($filters);
    }

    /**
     * Fetch a single inventory item by ID.
     */
    public function getInventoryById(int $id): ?Inventory
    {
        return $this->inventoryRepository->findById($id);
    }

    /**
     * Return all inventory records for the given product.
     *
     * @return Collection<int, Inventory>
     */
    public function getInventoryByProductId(int $productId): Collection
    {
        return $this->inventoryRepository->findByProductId($productId);
    }

    /**
     * Create a new inventory record and fire InventoryUpdated event.
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function createInventory(array $data): Inventory
    {
        return DB::transaction(function () use ($data): Inventory {
            $inventory = $this->inventoryRepository->create($data);

            if ($inventory->quantity > 0) {
                $this->transactionRepository->create([
                    'inventory_id'      => $inventory->id,
                    'product_id'        => $inventory->product_id,
                    'type'              => InventoryTransaction::TYPE_RECEIPT,
                    'quantity'          => $inventory->quantity,
                    'previous_quantity' => 0,
                    'new_quantity'      => $inventory->quantity,
                    'notes'             => 'Initial stock on inventory creation.',
                    'performed_by'      => 'system',
                ]);
            }

            Log::info('Inventory created', [
                'inventory_id' => $inventory->id,
                'product_id'   => $inventory->product_id,
            ]);

            return $inventory;
        });
    }

    /**
     * Update inventory metadata (not for stock quantity changes – use adjustStock).
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function updateInventory(int $id, array $data): ?Inventory
    {
        $inventory = $this->inventoryRepository->findById($id);

        if ($inventory === null) {
            return null;
        }

        return DB::transaction(function () use ($id, $data): Inventory {
            $updated = $this->inventoryRepository->update($id, $data);

            Log::info('Inventory updated', ['inventory_id' => $id]);

            return $updated;
        });
    }

    /**
     * Soft-delete an inventory record.
     *
     * @throws Throwable
     */
    public function deleteInventory(int $id): bool
    {
        $inventory = $this->inventoryRepository->findById($id);

        if ($inventory === null) {
            return false;
        }

        return DB::transaction(function () use ($id): bool {
            $deleted = $this->inventoryRepository->delete($id);

            if ($deleted) {
                Log::info('Inventory deleted', ['inventory_id' => $id]);
            }

            return $deleted;
        });
    }

    /**
     * Adjust stock quantity atomically (ACID).
     *
     * Supported types: receipt | adjustment | sale
     * Positive quantity always increases stock.
     * For 'adjustment' or 'sale', pass a negative quantity to decrease stock.
     *
     * @throws RuntimeException      if insufficient stock
     * @throws InvalidArgumentException if type is invalid
     * @throws Throwable
     */
    public function adjustStock(
        int $inventoryId,
        string $type,
        int $quantity,
        string $notes = '',
        ?string $referenceType = null,
        ?string $referenceId = null,
        string $performedBy = 'system'
    ): Inventory {
        if (! in_array($type, InventoryTransaction::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Invalid transaction type: {$type}");
        }

        return DB::transaction(function () use (
            $inventoryId, $type, $quantity, $notes, $referenceType, $referenceId, $performedBy
        ): Inventory {
            // Pessimistic lock to prevent race conditions
            $inventory = $this->inventoryRepository->lockForUpdate($inventoryId);

            if ($inventory === null) {
                throw new RuntimeException("Inventory record {$inventoryId} not found.");
            }

            $previousQuantity = $inventory->quantity;
            $newQuantity      = $previousQuantity + $quantity;

            if ($newQuantity < 0) {
                throw new RuntimeException(
                    "Insufficient stock. Available: {$previousQuantity}, requested reduction: " . abs($quantity)
                );
            }

            // Persist the transaction record first
            $this->transactionRepository->create([
                'inventory_id'      => $inventory->id,
                'product_id'        => $inventory->product_id,
                'type'              => $type,
                'quantity'          => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity'      => $newQuantity,
                'reference_type'    => $referenceType,
                'reference_id'      => $referenceId,
                'notes'             => $notes,
                'performed_by'      => $performedBy,
            ]);

            // Update the inventory quantity
            $inventory->update([
                'quantity'        => $newQuantity,
                'last_counted_at' => now(),
            ]);

            $inventory = $inventory->fresh();

            Log::info('Stock adjusted', [
                'inventory_id'      => $inventoryId,
                'type'              => $type,
                'quantity_change'   => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity'      => $newQuantity,
            ]);

            // Fire domain events
            event(new InventoryUpdated($inventory, $type, $quantity, ['quantity' => $previousQuantity]));

            if ($inventory->quantity <= 0) {
                event(new InventoryDepleted($inventory));
            } elseif ($inventory->is_low_stock) {
                event(new InventoryLow($inventory));
            }

            return $inventory;
        });
    }

    /**
     * Reserve stock for an order (reduces available quantity without reducing physical stock).
     *
     * @throws RuntimeException if insufficient available stock
     * @throws Throwable
     */
    public function reserveStock(
        int $inventoryId,
        int $quantity,
        string $referenceType = 'order',
        ?string $referenceId = null,
        string $performedBy = 'system'
    ): Inventory {
        return DB::transaction(function () use (
            $inventoryId, $quantity, $referenceType, $referenceId, $performedBy
        ): Inventory {
            $inventory = $this->inventoryRepository->lockForUpdate($inventoryId);

            if ($inventory === null) {
                throw new RuntimeException("Inventory record {$inventoryId} not found.");
            }

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $quantity) {
                throw new RuntimeException(
                    "Insufficient available stock. Available: {$available}, requested: {$quantity}"
                );
            }

            $previousReserved = $inventory->reserved_quantity;
            $newReserved      = $previousReserved + $quantity;

            $this->transactionRepository->create([
                'inventory_id'      => $inventory->id,
                'product_id'        => $inventory->product_id,
                'type'              => InventoryTransaction::TYPE_RESERVATION,
                'quantity'          => $quantity,
                'previous_quantity' => $previousReserved,
                'new_quantity'      => $newReserved,
                'reference_type'    => $referenceType,
                'reference_id'      => $referenceId,
                'notes'             => "Reserved {$quantity} unit(s) for {$referenceType} #{$referenceId}.",
                'performed_by'      => $performedBy,
            ]);

            $inventory->update(['reserved_quantity' => $newReserved]);

            $inventory = $inventory->fresh();

            Log::info('Stock reserved', [
                'inventory_id'    => $inventoryId,
                'quantity'        => $quantity,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
            ]);

            event(new InventoryUpdated($inventory, InventoryTransaction::TYPE_RESERVATION, $quantity, [
                'reserved_quantity' => $previousReserved,
            ]));

            return $inventory;
        });
    }

    /**
     * Release previously reserved stock (Saga compensating transaction).
     *
     * @throws RuntimeException if insufficient reserved quantity
     * @throws Throwable
     */
    public function releaseStock(
        int $inventoryId,
        int $quantity,
        string $referenceType = 'order',
        ?string $referenceId = null,
        string $performedBy = 'system'
    ): Inventory {
        return DB::transaction(function () use (
            $inventoryId, $quantity, $referenceType, $referenceId, $performedBy
        ): Inventory {
            $inventory = $this->inventoryRepository->lockForUpdate($inventoryId);

            if ($inventory === null) {
                throw new RuntimeException("Inventory record {$inventoryId} not found.");
            }

            if ($inventory->reserved_quantity < $quantity) {
                throw new RuntimeException(
                    "Cannot release more than reserved. Reserved: {$inventory->reserved_quantity}, requested: {$quantity}"
                );
            }

            $previousReserved = $inventory->reserved_quantity;
            $newReserved      = $previousReserved - $quantity;

            $this->transactionRepository->create([
                'inventory_id'      => $inventory->id,
                'product_id'        => $inventory->product_id,
                'type'              => InventoryTransaction::TYPE_RELEASE,
                'quantity'          => $quantity,
                'previous_quantity' => $previousReserved,
                'new_quantity'      => $newReserved,
                'reference_type'    => $referenceType,
                'reference_id'      => $referenceId,
                'notes'             => "Released {$quantity} unit(s) reserved for {$referenceType} #{$referenceId}.",
                'performed_by'      => $performedBy,
            ]);

            $inventory->update(['reserved_quantity' => $newReserved]);

            $inventory = $inventory->fresh();

            Log::info('Stock released', [
                'inventory_id'   => $inventoryId,
                'quantity'       => $quantity,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
            ]);

            event(new InventoryUpdated($inventory, InventoryTransaction::TYPE_RELEASE, -$quantity, [
                'reserved_quantity' => $previousReserved,
            ]));

            return $inventory;
        });
    }
}
