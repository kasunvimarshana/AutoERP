<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;

interface InventoryLevelRepositoryInterface extends RepositoryInterface
{
    public function save(InventoryLevel $level): InventoryLevel;

    public function findByProduct(int $tenantId, int $productId): Collection;

    public function findByProductAndLocation(int $tenantId, int $productId, ?int $locationId, ?int $batchId): ?InventoryLevel;

    public function getTotalStock(int $tenantId, int $productId): float;
}
