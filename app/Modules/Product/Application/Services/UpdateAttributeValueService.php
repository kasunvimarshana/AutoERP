<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateAttributeValueServiceInterface;
use Modules\Product\Application\DTOs\AttributeValueData;
use Modules\Product\Domain\Entities\AttributeValue;
use Modules\Product\Domain\Exceptions\AttributeValueNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\AttributeValueRepositoryInterface;

class UpdateAttributeValueService extends BaseService implements UpdateAttributeValueServiceInterface
{
    public function __construct(private readonly AttributeValueRepositoryInterface $attributeValueRepository)
    {
        parent::__construct($attributeValueRepository);
    }

    protected function handle(array $data): AttributeValue
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->attributeValueRepository->find($id);

        if (! $entity) {
            throw new AttributeValueNotFoundException($id);
        }

        $dto = AttributeValueData::fromArray($data);
        $entity->update(
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
