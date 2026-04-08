<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;

interface FindInventoryLevelServiceInterface extends ReadServiceInterface
{
    public function findByProduct(int $tenantId, int $productId): Collection;
    public function findByProductAndLocation(int $tenantId, int $productId, ?int $locationId, ?int $batchId): ?InventoryLevel;
    public function getTotalStock(int $tenantId, int $productId): float;
}
