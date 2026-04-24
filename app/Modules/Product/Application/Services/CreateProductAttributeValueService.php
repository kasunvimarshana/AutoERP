<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductAttributeValueServiceInterface;
use Modules\Product\Application\DTOs\ProductAttributeValueData;
use Modules\Product\Domain\Entities\ProductAttributeValue;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeValueRepositoryInterface;

class CreateProductAttributeValueService extends BaseService implements CreateProductAttributeValueServiceInterface
{
    public function __construct(private readonly ProductAttributeValueRepositoryInterface $attributeValueRepository)
    {
        parent::__construct($attributeValueRepository);
    }

    protected function handle(array $data): ProductAttributeValue
    {
        $dto = ProductAttributeValueData::fromArray($data);

        $attributeValue = new ProductAttributeValue(
            tenantId: $dto->tenant_id,
            attributeId: $dto->attribute_id,
            value: $dto->value,
            sortOrder: $dto->sort_order,
        );

        return $this->attributeValueRepository->save($attributeValue);
    }
}
