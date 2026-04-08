<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Collection;
use Modules\Product\Application\Contracts\FindProductVariationsServiceInterface;
use Modules\Product\Domain\Entities\ProductVariation;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariationRepositoryInterface;

class FindProductVariationsService implements FindProductVariationsServiceInterface
{
    public function __construct(
        private readonly ProductVariationRepositoryInterface $variationRepository
    ) {}

    public function findByProduct(int $productId): Collection
    {
        return $this->variationRepository->findByProduct($productId);
    }

    public function find(int $variationId): ?ProductVariation
    {
        return $this->variationRepository->find($variationId);
    }
}
