<?php

namespace App\Modules\Inventory\Repositories\Interfaces;

use App\Modules\Inventory\DTOs\InventoryDTO;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?InventoryItem;
    public function findByProductId(int $productId): ?InventoryItem;
    public function findByProductSku(string $sku): ?InventoryItem;
    public function create(InventoryDTO $dto): InventoryItem;
    public function update(int $id, array $data): ?InventoryItem;
    public function delete(int $id): bool;
    public function adjustStock(int $id, int $quantityChange): ?InventoryItem;
    public function reserveStock(int $id, int $quantity): bool;
    public function releaseStock(int $id, int $quantity): bool;
}
