<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts;

use Modules\Product\Domain\Entities\ProductAttribute;

interface ProductAttributeRepositoryInterface
{
    /**
     * Return all attributes for a given product, ordered by sort_order ASC.
     *
     * @return ProductAttribute[]
     */
    public function findByProduct(int $productId, int $tenantId): array;

    /**
     * Persist a single product attribute record.
     */
    public function save(ProductAttribute $attribute): ProductAttribute;

    /**
     * Replace all attributes for a product with the provided set.
     *
     * All existing attributes for the product are removed and re-inserted in a
     * single DB transaction to guarantee consistency.
     *
     * @param  ProductAttribute[]  $attributes
     */
    public function replaceForProduct(int $productId, int $tenantId, array $attributes): void;

    /**
     * Remove a single attribute by its ID within a tenant context.
     */
    public function delete(int $attributeId, int $tenantId): void;
}
