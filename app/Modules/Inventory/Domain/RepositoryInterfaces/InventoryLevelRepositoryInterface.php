<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\InventoryLevel;

interface InventoryLevelRepositoryInterface
{
    public function findById(int $id): ?InventoryLevel;
    public function findByProduct(int $tenantId, int $productId, int $warehouseId): ?InventoryLevel;
    public function findByWarehouse(int $tenantId, int $warehouseId): LengthAwarePaginator;
    public function upsert(int $tenantId, int $productId, int $warehouseId, ?int $locationId, string $valuationMethod): InventoryLevel;
    public function update(int $id, array $data): ?InventoryLevel;
}
