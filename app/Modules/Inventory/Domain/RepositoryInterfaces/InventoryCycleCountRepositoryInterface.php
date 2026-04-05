<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;

interface InventoryCycleCountRepositoryInterface
{
    public function findById(int $id): ?InventoryCycleCount;
    public function findByWarehouse(int $tenantId, int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): InventoryCycleCount;
    public function update(int $id, array $data): ?InventoryCycleCount;
    public function delete(int $id): bool;
}
