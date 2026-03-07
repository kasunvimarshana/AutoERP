<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepositoryInterface
{
    public function all(array $filters = []): Collection;

    public function find(int $id): ?Inventory;

    public function findByProduct(int $productId): ?Inventory;

    public function create(array $data): Inventory;

    public function update(int $id, array $data): Inventory;

    public function delete(int $id): bool;

    public function paginate(
        int $perPage,
        array $filters,
        ?string $search,
        string $sortBy,
        string $sortDir
    ): LengthAwarePaginator;

    public function adjustQuantity(int $id, int $delta, string $reason): Inventory;
}
