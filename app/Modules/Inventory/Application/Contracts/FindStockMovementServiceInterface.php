<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface FindStockMovementServiceInterface
{
    public function listByWarehouse(
        int $tenantId,
        int $warehouseId,
        array $filters = [],
        int $perPage = 15,
        int $page = 1,
        ?string $sort = null,
    ): mixed;
}
