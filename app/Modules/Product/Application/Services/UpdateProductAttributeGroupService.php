<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductAttributeGroupServiceInterface;
use Modules\Product\Application\DTOs\ProductAttributeGroupData;
use Modules\Product\Domain\Entities\ProductAttributeGroup;
use Modules\Product\Domain\Exceptions\ProductAttributeGroupNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeGroupRepositoryInterface;

class UpdateProductAttributeGroupService extends BaseService implements UpdateProductAttributeGroupServiceInterface
{
    public function __construct(private readonly ProductAttributeGroupRepositoryInterface $attributeGroupRepository)
    {
        parent::__construct($attributeGroupRepository);
    }

    protected function handle(array $data): ProductAttributeGroup
    {
        $id = (int) ($data['id'] ?? 0);
        $group = $this->attributeGroupRepository->find($id);

        if (! $group) {
            throw new ProductAttributeGroupNotFoundException($id);
        }

        $dto = ProductAttributeGroupData::fromArray($data);
        $group->update($dto->name);

        return $this->attributeGroupRepository->save($group);
    }
}
