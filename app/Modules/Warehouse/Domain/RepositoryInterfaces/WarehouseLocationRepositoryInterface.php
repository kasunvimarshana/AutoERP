<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Modules\Warehouse\Domain\Entities\WarehouseLocation;

interface WarehouseLocationRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?WarehouseLocation;

    /** @return WarehouseLocation[] */
    public function findAll(string $tenantId): array;

    /** @return WarehouseLocation[] */
    public function findByWarehouse(string $tenantId, string $warehouseId): array;

    /** @return WarehouseLocation[] */
    public function findChildren(string $tenantId, string $parentId): array;

    public function save(WarehouseLocation $location): void;

    public function delete(string $tenantId, string $id): void;
}
