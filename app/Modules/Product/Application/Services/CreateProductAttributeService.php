<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateProductAttributeServiceInterface;
use Modules\Product\Application\DTOs\ProductAttributeData;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeRepositoryInterface;

class CreateProductAttributeService extends BaseService implements CreateProductAttributeServiceInterface
{
    public function __construct(private readonly ProductAttributeRepositoryInterface $attributeRepository)
    {
        parent::__construct($attributeRepository);
    }

    protected function handle(array $data): ProductAttribute
    {
        $dto = ProductAttributeData::fromArray($data);

        $attribute = new ProductAttribute(
            tenantId: $dto->tenant_id,
            groupId: $dto->group_id,
            name: $dto->name,
            type: $dto->type,
            isRequired: $dto->is_required,
        );

        return $this->attributeRepository->save($attribute);
    }
}
