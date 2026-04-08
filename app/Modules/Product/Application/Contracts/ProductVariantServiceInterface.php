<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\ProductVariant;

interface ProductVariantServiceInterface
{
    public function getVariant(string $tenantId, string $id): ProductVariant;
    public function createVariant(string $tenantId, string $productId, array $data): ProductVariant;
    public function updateVariant(string $tenantId, string $id, array $data): ProductVariant;
    public function deleteVariant(string $tenantId, string $id): void;
    /** @return ProductVariant[] */
    public function getVariantsByProduct(string $tenantId, string $productId): array;
}
