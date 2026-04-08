<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface InventoryItemRepositoryInterface extends RepositoryInterface
{
    public function findByProductWarehouse(string $productId, string $warehouseId, ?string $variantId = null): mixed;
    public function findByProduct(string $productId): \Illuminate\Support\Collection;
}
