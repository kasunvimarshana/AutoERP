<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Product\Domain\Entities\ComboItem;

interface FindComboItemsServiceInterface
{
    /**
     * Return all combo items belonging to a product.
     *
     * @return Collection<int, ComboItem>
     */
    public function findByProduct(int $productId): Collection;

    /**
     * Find a single combo item by its primary key.
     */
    public function find(int $itemId): ?ComboItem;
}
