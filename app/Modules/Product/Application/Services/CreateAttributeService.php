<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateAttributeServiceInterface;
use Modules\Product\Application\DTOs\AttributeData;
use Modules\Product\Domain\Entities\Attribute;
use Modules\Product\Domain\RepositoryInterfaces\AttributeRepositoryInterface;

class CreateAttributeService extends BaseService implements CreateAttributeServiceInterface
{
    public function __construct(private readonly AttributeRepositoryInterface $attributeRepository)
    {
        parent::__construct($attributeRepository);
    }

    protected function handle(array $data): Attribute
    {
        $dto = AttributeData::fromArray($data);
        $entity = new Attribute(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            type: $dto->type,
            isRequired: $dto->is_required,
            groupId: $dto->group_id,
            code: $dto->code,
            description: $dto->description,
            sortOrder: $dto->sort_order,
            isActive: $dto->is_active,
            isFilterable: $dto->is_filterable,
            metadata: $dto->metadata,
        );

        return $this->attributeRepository->save($entity);
    }
}
