<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface InventoryMovementRepositoryInterface extends RepositoryInterface
{
    public function findByProduct(string $productId): \Illuminate\Support\Collection;
}
