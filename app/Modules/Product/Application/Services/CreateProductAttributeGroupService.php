<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductAttributeGroupServiceInterface;
use Modules\Product\Application\DTOs\ProductAttributeGroupData;
use Modules\Product\Domain\Entities\ProductAttributeGroup;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeGroupRepositoryInterface;

class CreateProductAttributeGroupService extends BaseService implements CreateProductAttributeGroupServiceInterface
{
    public function __construct(private readonly ProductAttributeGroupRepositoryInterface $attributeGroupRepository)
    {
        parent::__construct($attributeGroupRepository);
    }

    protected function handle(array $data): ProductAttributeGroup
    {
        $dto = ProductAttributeGroupData::fromArray($data);

        $group = new ProductAttributeGroup(
            tenantId: $dto->tenant_id,
            name: $dto->name,
        );

        return $this->attributeGroupRepository->save($group);
    }
}
