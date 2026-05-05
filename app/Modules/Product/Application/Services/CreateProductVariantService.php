<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;
use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class CreateProductVariantService extends BaseService implements CreateProductVariantServiceInterface
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    )
    {
        parent::__construct($productVariantRepository);
    }

    protected function handle(array $data): ProductVariant
    {
        $dto = ProductVariantData::fromArray($data);

        if ($dto->is_default) {
            $this->productVariantRepository->clearDefaultForProduct($dto->tenant_id, $dto->product_id);
        }

        $productVariant = new ProductVariant(
            productId: $dto->product_id,
            tenantId: $dto->tenant_id,
            name: $dto->name,
            sku: $dto->sku,
            isDefault: $dto->is_default,
            isActive: $dto->is_active,
            metadata: $dto->metadata,
        );

        $saved = $this->productVariantRepository->save($productVariant);
        $tenantId = $saved->getTenantId();
        if ($tenantId !== null) {
            $this->refreshProjectionService->execute($tenantId, $saved->getProductId());
        }

        return $saved;
    }
}
