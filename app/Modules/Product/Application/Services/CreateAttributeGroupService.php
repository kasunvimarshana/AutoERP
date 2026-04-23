<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateAttributeGroupServiceInterface;
use Modules\Product\Application\DTOs\AttributeGroupData;
use Modules\Product\Domain\Entities\AttributeGroup;
use Modules\Product\Domain\RepositoryInterfaces\AttributeGroupRepositoryInterface;

class CreateAttributeGroupService extends BaseService implements CreateAttributeGroupServiceInterface
{
    public function __construct(private readonly AttributeGroupRepositoryInterface $attributeGroupRepository)
    {
        parent::__construct($attributeGroupRepository);
    }

    protected function handle(array $data): AttributeGroup
    {
        $dto = AttributeGroupData::fromArray($data);
        $entity = new AttributeGroup(
            tenantId: $dto->tenant_id,
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
