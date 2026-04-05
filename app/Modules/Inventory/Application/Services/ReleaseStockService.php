<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class ReleaseStockService implements ReleaseStockServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $repo) {}

    public function execute(int $tenantId, int $productId, int $warehouseId, float $quantity): InventoryLevel
    {
        $level = $this->repo->findByProduct($tenantId, $productId, $warehouseId);
        if (!$level) throw new InsufficientStockException($productId, $quantity, 0);

        $level->releaseReservation($quantity);
        $this->repo->update($level->getId(), [
            'quantity_reserved' => $level->getQuantityReserved(),
        ]);

        return $level;
    }
}
