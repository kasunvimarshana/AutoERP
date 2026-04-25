<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductIdentifierServiceInterface;
use Modules\Product\Application\DTOs\ProductIdentifierData;
use Modules\Product\Domain\Entities\ProductIdentifier;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;

class CreateProductIdentifierService extends BaseService implements CreateProductIdentifierServiceInterface
{
    public function __construct(private readonly ProductIdentifierRepositoryInterface $productIdentifierRepository)
    {
        parent::__construct($productIdentifierRepository);
    }

    protected function handle(array $data): ProductIdentifier
    {
        $dto = ProductIdentifierData::fromArray($data);

        $productIdentifier = new ProductIdentifier(
            tenantId: $dto->tenant_id,
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

        return $this->productIdentifierRepository->save($productIdentifier);
    }
}
