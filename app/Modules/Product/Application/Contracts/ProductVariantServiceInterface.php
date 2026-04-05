<?php
declare(strict_types=1);
namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\ProductVariant;

interface ProductVariantServiceInterface
{
    public function listForProduct(int $tenantId, int $productId): array;
    public function create(int $tenantId, int $productId, array $data): ProductVariant;
    public function update(int $id, array $data): ProductVariant;
    public function delete(int $id): void;
}
