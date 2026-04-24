<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateVariantAttributeServiceInterface;
use Modules\Product\Application\DTOs\VariantAttributeData;
use Modules\Product\Domain\Entities\VariantAttribute;
use Modules\Product\Domain\Exceptions\VariantAttributeNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\VariantAttributeRepositoryInterface;

class UpdateVariantAttributeService extends BaseService implements UpdateVariantAttributeServiceInterface
{
    public function __construct(private readonly VariantAttributeRepositoryInterface $variantAttributeRepository)
    {
        parent::__construct($variantAttributeRepository);
    }

    protected function handle(array $data): VariantAttribute
    {
        $id = (int) ($data['id'] ?? 0);
        $variantAttribute = $this->variantAttributeRepository->find($id);

        if (! $variantAttribute) {
            throw new VariantAttributeNotFoundException($id);
        }

        $dto = VariantAttributeData::fromArray($data);
        $variantAttribute->update(
            isRequired: $dto->is_required,
            isVariationAxis: $dto->is_variation_axis,
            displayOrder: $dto->display_order,
        );

        return $this->variantAttributeRepository->save($variantAttribute);
    }
}
