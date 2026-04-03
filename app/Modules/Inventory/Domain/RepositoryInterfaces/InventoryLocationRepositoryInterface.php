<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryLocation;

interface InventoryLocationRepositoryInterface extends RepositoryInterface
{
    public function save(InventoryLocation $location): InventoryLocation;

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection;

    public function findByCode(int $tenantId, string $code): ?InventoryLocation;
}
