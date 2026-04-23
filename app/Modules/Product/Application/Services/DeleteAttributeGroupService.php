<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteAttributeGroupServiceInterface;
use Modules\Product\Domain\Exceptions\AttributeGroupNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\AttributeGroupRepositoryInterface;

class DeleteAttributeGroupService extends BaseService implements DeleteAttributeGroupServiceInterface
{
    public function __construct(private readonly AttributeGroupRepositoryInterface $attributeGroupRepository)
    {
        parent::__construct($attributeGroupRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->attributeGroupRepository->find($id);

        if (! $entity) {
            throw new AttributeGroupNotFoundException($id);
        }

        return $this->attributeGroupRepository->delete($id);
    }
}
