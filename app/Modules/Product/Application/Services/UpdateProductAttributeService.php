<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductAttributeServiceInterface;
use Modules\Product\Application\DTOs\ProductAttributeData;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Domain\Exceptions\ProductAttributeNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeRepositoryInterface;

class UpdateProductAttributeService extends BaseService implements UpdateProductAttributeServiceInterface
{
    public function __construct(private readonly ProductAttributeRepositoryInterface $attributeRepository)
    {
        parent::__construct($attributeRepository);
    }

    protected function handle(array $data): ProductAttribute
    {
        $id = (int) ($data['id'] ?? 0);
        $attribute = $this->attributeRepository->find($id);

        if (! $attribute) {
            throw new ProductAttributeNotFoundException($id);
        }

        $dto = ProductAttributeData::fromArray($data);
        $attribute->update(
            name: $dto->name,
            type: $dto->type,
            isRequired: $dto->is_required,
            groupId: $dto->group_id,
        );

        return $this->attributeRepository->save($attribute);
    }
}
