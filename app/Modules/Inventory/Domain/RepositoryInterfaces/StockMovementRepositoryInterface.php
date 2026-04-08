<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface StockMovementRepositoryInterface extends RepositoryInterface
{
    public function findByProduct(int $productId, int $tenantId): Collection;

    public function findByReference(string $reference, int $tenantId): Collection;
}
