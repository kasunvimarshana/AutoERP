<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface WarehouseLocationRepositoryInterface extends RepositoryInterface
{
    public function findByWarehouse(string $warehouseId): Collection;
    public function findChildren(string $locationId): Collection;
}
