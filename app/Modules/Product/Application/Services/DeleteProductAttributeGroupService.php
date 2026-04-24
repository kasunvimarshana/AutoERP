<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductAttributeGroupServiceInterface;
use Modules\Product\Domain\Exceptions\ProductAttributeGroupNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeGroupRepositoryInterface;

class DeleteProductAttributeGroupService extends BaseService implements DeleteProductAttributeGroupServiceInterface
{
    public function __construct(private readonly ProductAttributeGroupRepositoryInterface $attributeGroupRepository)
    {
        parent::__construct($attributeGroupRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $group = $this->attributeGroupRepository->find($id);

        if (! $group) {
            throw new ProductAttributeGroupNotFoundException($id);
        }

        return $this->attributeGroupRepository->delete($id);
    }
}
