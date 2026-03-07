<?php

namespace App\Repositories\Interfaces;

use App\Models\Inventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepositoryInterface
{
    public function findById(int $id): ?Inventory;

    public function findByProductAndWarehouse(int $productId, int $warehouseId): ?Inventory;

    public function findByProductId(int $productId): Collection;

    public function create(array $data): Inventory;

    public function update(Inventory $inventory, array $data): Inventory;

    public function delete(Inventory $inventory): bool;

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection;
}
