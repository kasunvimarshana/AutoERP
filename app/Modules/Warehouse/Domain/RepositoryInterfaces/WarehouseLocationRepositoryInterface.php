<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;

interface WarehouseLocationRepositoryInterface extends RepositoryInterface
{
    public function save(WarehouseLocation $location): WarehouseLocation;

    public function findByTenantWarehouseAndCode(int $tenantId, int $warehouseId, string $code): ?WarehouseLocation;

    /**
     * @return list<WarehouseLocation>
     */
    public function listByWarehouse(int $tenantId, int $warehouseId): array;

    public function updateDescendantPaths(int $tenantId, int $warehouseId, string $oldPrefix, string $newPrefix): void;
}
