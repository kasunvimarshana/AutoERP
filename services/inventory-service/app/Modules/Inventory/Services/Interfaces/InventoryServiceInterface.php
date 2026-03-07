<?php

namespace App\Modules\Inventory\Services\Interfaces;

use App\Modules\Inventory\DTOs\InventoryDTO;
use App\Modules\Inventory\DTOs\StockAdjustmentDTO;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryServiceInterface
{
    public function listInventory(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function getInventoryItem(int $id): InventoryItem;
    public function createInventoryItem(InventoryDTO $dto): InventoryItem;
    public function updateInventoryItem(int $id, array $data): InventoryItem;
    public function deleteInventoryItem(int $id): void;
    public function adjustStock(StockAdjustmentDTO $dto): InventoryItem;
    public function reserveStock(int $inventoryId, int $quantity): bool;
    public function releaseStock(int $inventoryId, int $quantity): bool;
    public function getInventoryByProduct(int $productId): ?InventoryItem;
}
