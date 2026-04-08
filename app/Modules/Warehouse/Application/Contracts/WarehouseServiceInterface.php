<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Domain\Entities\Warehouse;

interface WarehouseServiceInterface
{
    public function getWarehouse(string $tenantId, string $id): Warehouse;

    /** @return Warehouse[] */
    public function getAllWarehouses(string $tenantId): array;

    public function createWarehouse(string $tenantId, array $data): Warehouse;

    public function updateWarehouse(string $tenantId, string $id, array $data): Warehouse;

    public function deleteWarehouse(string $tenantId, string $id): void;
}
