<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductIdentifierServiceInterface;
use Modules\Product\Application\DTOs\ProductIdentifierData;
use Modules\Product\Domain\Entities\ProductIdentifier;
use Modules\Product\Domain\Exceptions\ProductIdentifierNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;

class UpdateProductIdentifierService extends BaseService implements UpdateProductIdentifierServiceInterface
{
    public function __construct(
        private readonly ProductIdentifierRepositoryInterface $productIdentifierRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    )
    {
        parent::__construct($productIdentifierRepository);
    }

    protected function handle(array $data): ProductIdentifier
    {
        $id = (int) ($data['id'] ?? 0);
        $productIdentifier = $this->productIdentifierRepository->find($id);

        if (! $productIdentifier) {
            throw new ProductIdentifierNotFoundException($id);
        }

        $dto = ProductIdentifierData::fromArray($data);

        $productIdentifier->update(
            productId: $dto->product_id,
            technology: $dto->technology,
            value: $dto->value,
            variantId: $dto->variant_id,
            batchId: $dto->batch_id,
            serialId: $dto->serial_id,
            format: $dto->format,
            gs1CompanyPrefix: $dto->gs1_company_prefix,
            gs1ApplicationIdentifiers: $dto->gs1_application_identifiers,
            isPrimary: $dto->is_primary,
            isActive: $dto->is_active,
            formatConfig: $dto->format_config,
            metadata: $dto->metadata,
        );

        $saved = $this->productIdentifierRepository->save($productIdentifier);
        $this->refreshProjectionService->execute($saved->getTenantId(), $saved->getProductId());

        return $saved;
    }
}
