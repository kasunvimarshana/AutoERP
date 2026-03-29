<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Product\Domain\Entities\ProductVariation;

interface FindProductVariationsServiceInterface
{
    /**
     * Return all variations belonging to a product.
     *
     * @return Collection<int, ProductVariation>
     */
    public function findByProduct(int $productId): Collection;

    /**
     * Find a single variation by its primary key.
     */
    public function find(int $variationId): ?ProductVariation;
}
