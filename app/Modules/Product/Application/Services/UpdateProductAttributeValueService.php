<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductAttributeValueServiceInterface;
use Modules\Product\Application\DTOs\ProductAttributeValueData;
use Modules\Product\Domain\Entities\ProductAttributeValue;
use Modules\Product\Domain\Exceptions\ProductAttributeValueNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeValueRepositoryInterface;

class UpdateProductAttributeValueService extends BaseService implements UpdateProductAttributeValueServiceInterface
{
    public function __construct(private readonly ProductAttributeValueRepositoryInterface $attributeValueRepository)
    {
        parent::__construct($attributeValueRepository);
    }

    protected function handle(array $data): ProductAttributeValue
    {
        $id = (int) ($data['id'] ?? 0);
        $attributeValue = $this->attributeValueRepository->find($id);

        if (! $attributeValue) {
            throw new ProductAttributeValueNotFoundException($id);
        }

        $dto = ProductAttributeValueData::fromArray($data);
        $attributeValue->update(
            value: $dto->value,
            sortOrder: $dto->sort_order,
        );

        return $this->attributeValueRepository->save($attributeValue);
    }
}
