<?php
declare(strict_types=1);
namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class ProductVariantService implements ProductVariantServiceInterface
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepo,
        private readonly ProductRepositoryInterface $productRepo,
    ) {}

    public function listForProduct(int $tenantId, int $productId): array
    {
        return $this->variantRepo->findByProduct($tenantId, $productId);
    }

    public function create(int $tenantId, int $productId, array $data): ProductVariant
    {
        $product = $this->productRepo->findById($productId);
        if (!$product || $product->getTenantId() !== $tenantId) {
            throw new ProductNotFoundException($productId);
        }

        return $this->variantRepo->create(array_merge($data, [
            'tenant_id'  => $tenantId,
            'product_id' => $productId,
        ]));
    }

    public function update(int $id, array $data): ProductVariant
    {
        $variant = $this->variantRepo->update($id, $data);
        if (!$variant) {
            throw new \DomainException("ProductVariant [{$id}] not found.");
        }
        return $variant;
    }

    public function delete(int $id): void
    {
        $this->variantRepo->delete($id);
    }
}
