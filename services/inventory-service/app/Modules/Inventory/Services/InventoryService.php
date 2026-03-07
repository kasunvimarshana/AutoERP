<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\DTOs\InventoryDTO;
use App\Modules\Inventory\DTOs\StockAdjustmentDTO;
use App\Modules\Inventory\Events\InventoryUpdated;
use App\Modules\Inventory\Events\StockReserved;
use App\Modules\Inventory\Events\StockReleased;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Modules\Inventory\Services\Interfaces\InventoryServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository,
    ) {}

    public function listInventory(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->inventoryRepository->paginate($filters, $perPage);
    }

    public function getInventoryItem(int $id): InventoryItem
    {
        $item = $this->inventoryRepository->findById($id);
        if (!$item) {
            throw new ModelNotFoundException("Inventory item not found with ID: {$id}");
        }
        return $item;
    }

    public function createInventoryItem(InventoryDTO $dto): InventoryItem
    {
        return DB::transaction(function () use ($dto) {
            $existing = $this->inventoryRepository->findByProductId($dto->productId);
            if ($existing) {
                throw new \InvalidArgumentException("Inventory item already exists for product ID: {$dto->productId}");
            }

            $item = $this->inventoryRepository->create($dto);
            Event::dispatch(new InventoryUpdated($item, 'created'));
            return $item;
        });
    }

    public function updateInventoryItem(int $id, array $data): InventoryItem
    {
        return DB::transaction(function () use ($id, $data) {
            $this->getInventoryItem($id);
            $updated = $this->inventoryRepository->update($id, $data);
            Event::dispatch(new InventoryUpdated($updated, 'updated'));
            return $updated;
        });
    }

    public function deleteInventoryItem(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->getInventoryItem($id);
            $this->inventoryRepository->delete($id);
        });
    }

    public function adjustStock(StockAdjustmentDTO $dto): InventoryItem
    {
        return DB::transaction(function () use ($dto) {
            $this->getInventoryItem($dto->inventoryItemId);

            $quantityChange = $dto->type === 'add' ? abs($dto->quantity) : -abs($dto->quantity);

            $updated = $this->inventoryRepository->adjustStock($dto->inventoryItemId, $quantityChange);

            Event::dispatch(new InventoryUpdated($updated, 'stock_adjusted', [
                'type' => $dto->type,
                'quantity' => $dto->quantity,
                'reason' => $dto->reason,
                'reference' => $dto->reference,
            ]));

            return $updated;
        });
    }

    public function reserveStock(int $inventoryId, int $quantity): bool
    {
        return DB::transaction(function () use ($inventoryId, $quantity) {
            $item = $this->getInventoryItem($inventoryId);
            $result = $this->inventoryRepository->reserveStock($inventoryId, $quantity);

            if ($result) {
                Event::dispatch(new StockReserved($item->fresh(), $quantity));
            }

            return $result;
        });
    }

    public function releaseStock(int $inventoryId, int $quantity): bool
    {
        return DB::transaction(function () use ($inventoryId, $quantity) {
            $item = $this->getInventoryItem($inventoryId);
            $result = $this->inventoryRepository->releaseStock($inventoryId, $quantity);

            if ($result) {
                Event::dispatch(new StockReleased($item->fresh(), $quantity));
            }

            return $result;
        });
    }

    public function getInventoryByProduct(int $productId): ?InventoryItem
    {
        return $this->inventoryRepository->findByProductId($productId);
    }
}
