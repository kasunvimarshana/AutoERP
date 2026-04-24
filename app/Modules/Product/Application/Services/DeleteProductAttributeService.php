<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductAttributeServiceInterface;
use Modules\Product\Domain\Exceptions\ProductAttributeNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeRepositoryInterface;

class DeleteProductAttributeService extends BaseService implements DeleteProductAttributeServiceInterface
{
    public function __construct(private readonly ProductAttributeRepositoryInterface $attributeRepository)
    {
        parent::__construct($attributeRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $attribute = $this->attributeRepository->find($id);

        if (! $attribute) {
            throw new ProductAttributeNotFoundException($id);
        }

        return $this->attributeRepository->delete($id);
    }
}
