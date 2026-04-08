<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts;

use Modules\Warehouse\Domain\Entities\WarehouseLocation;

interface WarehouseLocationServiceInterface
{
    public function getLocation(string $tenantId, string $id): WarehouseLocation;

    /** @return array<int, array<string, mixed>> */
    public function getTree(string $tenantId, string $warehouseId): array;

    public function createLocation(string $tenantId, array $data): WarehouseLocation;

    public function updateLocation(string $tenantId, string $id, array $data): WarehouseLocation;

    public function deleteLocation(string $tenantId, string $id): void;
}
