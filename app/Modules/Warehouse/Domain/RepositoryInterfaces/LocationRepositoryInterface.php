<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface LocationRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code, int $tenantId): mixed;

    public function findByWarehouse(int $warehouseId): Collection;

    public function getLocationTree(int $warehouseId): Collection;

    public function findChildren(int $parentId): Collection;
}
