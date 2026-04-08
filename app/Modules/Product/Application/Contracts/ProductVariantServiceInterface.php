<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Product\Application\DTOs\ProductVariantData;

interface ProductVariantServiceInterface
{
    public function create(ProductVariantData $dto): mixed;
    public function getByProduct(int $productId): Collection;
}
