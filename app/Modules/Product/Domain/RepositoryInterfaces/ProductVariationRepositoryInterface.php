<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductVariation;

interface ProductVariationRepositoryInterface extends RepositoryInterface
{
    /** @return Collection<int, ProductVariation> */
    public function findByProduct(int $productId): Collection;

    public function save(ProductVariation $variation): ProductVariation;
}
