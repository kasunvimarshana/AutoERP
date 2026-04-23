<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteAttributeServiceInterface;
use Modules\Product\Domain\Exceptions\AttributeNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\AttributeRepositoryInterface;

class DeleteAttributeService extends BaseService implements DeleteAttributeServiceInterface
{
    public function __construct(private readonly AttributeRepositoryInterface $attributeRepository)
    {
        parent::__construct($attributeRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->attributeRepository->find($id);

        if (! $entity) {
            throw new AttributeNotFoundException($id);
        }

        return $this->attributeRepository->delete($id);
    }
}
