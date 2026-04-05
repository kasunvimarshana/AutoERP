<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\InventoryLevel;

interface ReleaseStockServiceInterface
{
    public function execute(int $tenantId, int $productId, int $warehouseId, float $quantity): InventoryLevel;
}
