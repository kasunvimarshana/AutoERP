<?php
namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Product\Domain\Entities\ProductVariant;

interface ProductVariantRepositoryInterface
{
    public function findById(int $id): ?ProductVariant;
    public function findByProduct(int $productId): array;
    public function create(array $data): ProductVariant;
    public function update(ProductVariant $variant, array $data): ProductVariant;
    public function delete(ProductVariant $variant): bool;
}
