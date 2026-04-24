<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteProductAttributeValueServiceInterface;
use Modules\Product\Domain\Exceptions\ProductAttributeValueNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeValueRepositoryInterface;

class DeleteProductAttributeValueService extends BaseService implements DeleteProductAttributeValueServiceInterface
{
    public function __construct(private readonly ProductAttributeValueRepositoryInterface $attributeValueRepository)
    {
        parent::__construct($attributeValueRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $attributeValue = $this->attributeValueRepository->find($id);

        if (! $attributeValue) {
            throw new ProductAttributeValueNotFoundException($id);
        }

        return $this->attributeValueRepository->delete($id);
    }
}
