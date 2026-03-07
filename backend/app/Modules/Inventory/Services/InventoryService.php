<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Events\InventoryUpdated;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Repositories\InventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
    ) {}

    public function listInventory(
        int $perPage = 15,
        array $filters = [],
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        return $this->inventoryRepository->paginate($perPage, $filters, $search, $sortBy, $sortDir);
    }

    public function getInventory(int $id): ?Inventory
    {
        return $this->inventoryRepository->find($id);
    }

    public function createInventory(array $data): Inventory
    {
        return DB::transaction(fn() => $this->inventoryRepository->create($data));
    }

    public function updateInventory(int $id, array $data): Inventory
    {
        return DB::transaction(fn() => $this->inventoryRepository->update($id, $data));
    }

    public function deleteInventory(int $id): bool
    {
        return $this->inventoryRepository->delete($id);
    }

    public function adjustStock(int $inventoryId, int $delta, string $reason): Inventory
    {
        $inventory = $this->inventoryRepository->adjustQuantity($inventoryId, $delta, $reason);
        Event::dispatch(new InventoryUpdated($inventory, $delta, $reason));
        return $inventory;
    }
}
