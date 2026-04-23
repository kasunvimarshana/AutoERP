<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteAttributeValueServiceInterface;
use Modules\Product\Domain\Exceptions\AttributeValueNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\AttributeValueRepositoryInterface;

class DeleteAttributeValueService extends BaseService implements DeleteAttributeValueServiceInterface
{
    public function __construct(private readonly AttributeValueRepositoryInterface $attributeValueRepository)
    {
        parent::__construct($attributeValueRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->attributeValueRepository->find($id);

        if (! $entity) {
            throw new AttributeValueNotFoundException($id);
        }

        return $this->attributeValueRepository->delete($id);
    }
}
