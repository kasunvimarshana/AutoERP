<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface StockItemRepositoryInterface extends RepositoryInterface
{
    public function findByProductAndLocation(int $productId, int $locationId, ?int $variantId = null): mixed;

    public function findByProduct(int $productId, int $tenantId): Collection;

    public function findByLocation(int $locationId): Collection;

    public function decrementQuantity(int $id, float $qty): void;

    public function incrementQuantity(int $id, float $qty): void;
}
