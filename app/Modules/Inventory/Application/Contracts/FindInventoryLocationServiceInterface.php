<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLocation;

interface FindInventoryLocationServiceInterface extends ReadServiceInterface
{
    public function findByWarehouse(int $tenantId, int $warehouseId): Collection;
    public function findByCode(int $tenantId, string $code): ?InventoryLocation;
}
