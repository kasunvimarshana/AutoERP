<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateAttributeGroupServiceInterface;
use Modules\Product\Application\DTOs\AttributeGroupData;
use Modules\Product\Domain\Entities\AttributeGroup;
use Modules\Product\Domain\Exceptions\AttributeGroupNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\AttributeGroupRepositoryInterface;

class UpdateAttributeGroupService extends BaseService implements UpdateAttributeGroupServiceInterface
{
    public function __construct(private readonly AttributeGroupRepositoryInterface $attributeGroupRepository)
    {
        parent::__construct($attributeGroupRepository);
    }

    protected function handle(array $data): AttributeGroup
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->attributeGroupRepository->find($id);

        if (! $entity) {
            throw new AttributeGroupNotFoundException($id);
        }

        $dto = AttributeGroupData::fromArray($data);
        $entity->update(
            name: $dto->name,
            code: $dto->code,
            description: $dto->description,
            sortOrder: $dto->sort_order,
            isActive: $dto->is_active,
            metadata: $dto->metadata,
        );

        return $this->attributeGroupRepository->save($entity);
    }
}
