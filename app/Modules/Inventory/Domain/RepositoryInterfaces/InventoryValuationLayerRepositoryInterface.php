<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

interface InventoryValuationLayerRepositoryInterface extends RepositoryInterface
{
    public function save(InventoryValuationLayer $layer): InventoryValuationLayer;

    public function findOpenLayers(int $tenantId, int $productId, string $valuationMethod): Collection;

    public function findByProduct(int $tenantId, int $productId): Collection;
}
