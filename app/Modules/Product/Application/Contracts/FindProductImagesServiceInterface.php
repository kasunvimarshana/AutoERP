<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Product\Domain\Entities\ProductImage;

/**
 * Contract for querying product images.
 *
 * Separates image read operations from write concerns, adhering to
 * the Interface Segregation and Single Responsibility principles.
 * Controllers must depend on this interface rather than on
 * ProductImageRepositoryInterface directly.
 */
interface FindProductImagesServiceInterface
{
    /**
     * Return all images belonging to a product, ordered by sort_order.
     *
     * @return Collection<int, ProductImage>
     */
    public function findByProduct(int $productId): Collection;

    /**
     * Find a single image by its UUID.
     */
    public function findByUuid(string $uuid): ?ProductImage;
}
