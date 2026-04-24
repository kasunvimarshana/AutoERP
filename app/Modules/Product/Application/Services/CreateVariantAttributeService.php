<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateVariantAttributeServiceInterface;
use Modules\Product\Application\DTOs\VariantAttributeData;
use Modules\Product\Domain\Entities\VariantAttribute;
use Modules\Product\Domain\RepositoryInterfaces\VariantAttributeRepositoryInterface;

class CreateVariantAttributeService extends BaseService implements CreateVariantAttributeServiceInterface
{
    public function __construct(private readonly VariantAttributeRepositoryInterface $variantAttributeRepository)
    {
        parent::__construct($variantAttributeRepository);
    }

    protected function handle(array $data): VariantAttribute
    {
        $dto = VariantAttributeData::fromArray($data);

        $variantAttribute = new VariantAttribute(
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
            attributeId: $dto->attribute_id,
            isRequired: $dto->is_required,
            isVariationAxis: $dto->is_variation_axis,
            displayOrder: $dto->display_order,
        );

        return $this->variantAttributeRepository->save($variantAttribute);
    }
}
