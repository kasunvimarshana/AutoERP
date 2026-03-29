<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Collection;
use Modules\Product\Application\Contracts\FindProductImagesServiceInterface;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;

/**
 * Dedicated read service for product images.
 *
 * Wraps the image repository so that higher-level layers (controllers,
 * use-cases) depend only on this service interface rather than on the
 * repository abstraction, keeping the dependency graph clean.
 */
class FindProductImagesService implements FindProductImagesServiceInterface
{
    public function __construct(
        private readonly ProductImageRepositoryInterface $imageRepository
    ) {}

    /**
     * @return Collection<int, ProductImage>
     */
    public function findByProduct(int $productId): Collection
    {
        return $this->imageRepository->getByProduct($productId);
    }

    public function findByUuid(string $uuid): ?ProductImage
    {
        return $this->imageRepository->findByUuid($uuid);
    }
}
