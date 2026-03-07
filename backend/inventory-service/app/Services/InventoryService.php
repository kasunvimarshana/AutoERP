<?php

namespace App\Services;

use App\DTOs\InventoryDTO;
use App\Events\InventoryLow;
use App\Events\InventoryUpdated;
use App\Models\Inventory;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly ProductServiceClient $productClient,
    ) {}

    public function list(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        // Cross-service filter: if filtering by product name, first resolve product IDs
        if (! empty($filters['product_name'])) {
            $products = $this->productClient->searchByName($filters['product_name']);
            $filters['product_ids'] = array_column($products, 'id');
            unset($filters['product_name']);

            if (empty($filters['product_ids'])) {
                // No products matched, return empty result
                return $this->inventoryRepository->paginate(['product_ids' => [-1]], $perPage);
            }
        }

        return $this->inventoryRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Inventory
    {
        return $this->inventoryRepository->findById($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Inventory record not found.');
    }

    public function create(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            $existing = $this->inventoryRepository->findByProductAndWarehouse(
                $data['product_id'],
                $data['warehouse_id']
            );

            if ($existing) {
                throw ValidationException::withMessages([
                    'product_id' => ['Inventory record already exists for this product/warehouse combination.'],
                ]);
            }

            $dto = InventoryDTO::fromArray($data);
            $inventory = $this->inventoryRepository->create($dto->toArray());

            event(new InventoryUpdated($inventory, 'created'));

            return $inventory;
        });
    }

    public function update(int $id, array $data): Inventory
    {
        return DB::transaction(function () use ($id, $data) {
            $inventory = $this->findById($id);
            $updated = $this->inventoryRepository->update($inventory, $data);

            event(new InventoryUpdated($updated, 'updated'));

            if ($updated->isLow()) {
                event(new InventoryLow($updated));
            }

            return $updated;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $inventory = $this->findById($id);
            $this->inventoryRepository->delete($inventory);
        });
    }

    public function adjust(int $id, int $quantity, string $reason = ''): Inventory
    {
        return DB::transaction(function () use ($id, $quantity, $reason) {
            $inventory = $this->findById($id);

            $newQuantity = $inventory->quantity + $quantity;
            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Adjustment would result in negative inventory.'],
                ]);
            }

            $updated = $this->inventoryRepository->update($inventory, ['quantity' => $newQuantity]);

            event(new InventoryUpdated($updated, 'adjusted', ['adjustment' => $quantity, 'reason' => $reason]));

            if ($updated->isLow()) {
                event(new InventoryLow($updated));
            }

            return $updated;
        });
    }

    /**
     * Reserve inventory for an order (Saga participant - execute step).
     * Returns true on success, throws on failure.
     */
    public function reserve(int $productId, int $warehouseId, int $quantity): Inventory
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $inventory = Inventory::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \RuntimeException("No inventory found for product {$productId} in warehouse {$warehouseId}.");
            }

            if ($inventory->available_quantity < $quantity) {
                throw new \RuntimeException(
                    "Insufficient inventory. Available: {$inventory->available_quantity}, Requested: {$quantity}."
                );
            }

            $inventory->update(['reserved_quantity' => $inventory->reserved_quantity + $quantity]);

            event(new InventoryUpdated($inventory->fresh(), 'reserved', ['quantity_reserved' => $quantity]));

            return $inventory->fresh();
        });
    }

    /**
     * Release previously reserved inventory (Saga compensating transaction).
     */
    public function release(int $productId, int $warehouseId, int $quantity): Inventory
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $inventory = Inventory::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \RuntimeException("No inventory found for product {$productId} in warehouse {$warehouseId}.");
            }

            $newReserved = max(0, $inventory->reserved_quantity - $quantity);
            $inventory->update(['reserved_quantity' => $newReserved]);

            event(new InventoryUpdated($inventory->fresh(), 'released', ['quantity_released' => $quantity]));

            return $inventory->fresh();
        });
    }

    /**
     * Confirm reserved inventory (deduct from actual quantity after order completion).
     */
    public function confirm(int $productId, int $warehouseId, int $quantity): Inventory
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $inventory = Inventory::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \RuntimeException('No inventory found.');
            }

            $inventory->update([
                'quantity' => max(0, $inventory->quantity - $quantity),
                'reserved_quantity' => max(0, $inventory->reserved_quantity - $quantity),
            ]);

            event(new InventoryUpdated($inventory->fresh(), 'confirmed', ['quantity_confirmed' => $quantity]));

            return $inventory->fresh();
        });
    }
}
