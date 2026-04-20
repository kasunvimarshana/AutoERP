<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface FindStockLevelServiceInterface
{
    public function listByWarehouse(int $tenantId, int $warehouseId, int $perPage = 15, int $page = 1): mixed;
}
