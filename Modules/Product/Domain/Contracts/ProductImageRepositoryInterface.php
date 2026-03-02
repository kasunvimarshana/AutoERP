<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts;

use Modules\Product\Domain\Entities\ProductImage;

interface ProductImageRepositoryInterface
{
    /**
     * Return all images for a given product, ordered by sort_order ASC.
     *
     * @return ProductImage[]
     */
    public function findByProduct(int $productId, int $tenantId): array;

    /**
     * Persist a single product image record.
     */
    public function save(ProductImage $image): ProductImage;

    /**
     * Replace all images for a product with the provided set.
     *
     * All existing images for the product are removed and re-inserted in a
     * single DB transaction to guarantee consistency.
     *
     * @param  ProductImage[]  $images
     */
    public function replaceForProduct(int $productId, int $tenantId, array $images): void;

    /**
     * Remove a single image by its ID within a tenant context.
     */
    public function delete(int $imageId, int $tenantId): void;
}
