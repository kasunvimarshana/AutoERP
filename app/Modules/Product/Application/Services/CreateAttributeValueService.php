<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateAttributeValueServiceInterface;
use Modules\Product\Application\DTOs\AttributeValueData;
use Modules\Product\Domain\Entities\AttributeValue;
use Modules\Product\Domain\RepositoryInterfaces\AttributeValueRepositoryInterface;

class CreateAttributeValueService extends BaseService implements CreateAttributeValueServiceInterface
{
    public function __construct(private readonly AttributeValueRepositoryInterface $attributeValueRepository)
    {
        parent::__construct($attributeValueRepository);
    }

    protected function handle(array $data): AttributeValue
    {
        $dto = AttributeValueData::fromArray($data);
        $entity = new AttributeValue(
            tenantId: $dto->tenant_id,
            attributeId: $dto->attribute_id,
            value: $dto->value,
            sortOrder: $dto->sort_order,
            label: $dto->label,
            colorCode: $dto->color_code,
            isActive: $dto->is_active,
            metadata: $dto->metadata,
        );

        return $this->attributeValueRepository->save($entity);
    }
}
