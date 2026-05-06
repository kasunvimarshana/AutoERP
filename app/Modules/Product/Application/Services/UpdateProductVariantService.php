<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\Exceptions\ProductVariantNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class UpdateProductVariantService extends BaseService implements UpdateProductVariantServiceInterface
{
    public function __construct(private readonly ProductVariantRepositoryInterface $productVariantRepository)
    {
        parent::__construct($productVariantRepository);
    }

    protected function handle(array $data): ProductVariant
    {
        $id = (int) ($data['id'] ?? 0);
        $productVariant = $this->productVariantRepository->find($id);

        if (! $productVariant) {
            throw new ProductVariantNotFoundException($id);
        }

        $dto = ProductVariantData::fromArray($data);

        if ($productVariant->getTenantId() !== null && $productVariant->getTenantId() !== $dto->tenant_id) {
            throw new ProductVariantNotFoundException($id);
        }

        if ($dto->is_default) {
            $this->productVariantRepository->clearDefaultForProduct(
                tenantId: $dto->tenant_id,
                productId: $dto->product_id,
                exceptVariantId: $id,
            );
        }

        $productVariant->update(
            name: $dto->name,
            sku: $dto->sku,
            isDefault: $dto->is_default,
            isActive: $dto->is_active,
            metadata: $dto->metadata,
        );

        return $this->productVariantRepository->save($productVariant);
    }
}
