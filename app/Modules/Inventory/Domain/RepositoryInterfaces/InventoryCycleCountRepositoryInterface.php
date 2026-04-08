<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;

interface InventoryCycleCountRepositoryInterface extends RepositoryInterface
{
    public function save(InventoryCycleCount $cycleCount): InventoryCycleCount;

    public function findByWarehouse(int $tenantId, int $warehouseId): Collection;

    public function findByStatus(int $tenantId, string $status): Collection;
}
