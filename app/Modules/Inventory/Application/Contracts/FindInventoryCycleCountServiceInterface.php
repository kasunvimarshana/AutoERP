<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindInventoryCycleCountServiceInterface extends ReadServiceInterface
{
    public function findByWarehouse(int $tenantId, int $warehouseId): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
}
