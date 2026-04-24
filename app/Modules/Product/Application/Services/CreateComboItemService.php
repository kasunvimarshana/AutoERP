<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateComboItemServiceInterface;
use Modules\Product\Application\DTOs\ComboItemData;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;

class CreateComboItemService extends BaseService implements CreateComboItemServiceInterface
{
    public function __construct(private readonly ComboItemRepositoryInterface $comboItemRepository)
    {
        parent::__construct($comboItemRepository);
    }

    protected function handle(array $data): ComboItem
    {
        $dto = ComboItemData::fromArray($data);

        $comboItem = new ComboItem(
            tenantId: $dto->tenant_id,
            comboProductId: $dto->combo_product_id,
            componentProductId: $dto->component_product_id,
            componentVariantId: $dto->component_variant_id,
            quantity: $dto->quantity,
            uomId: $dto->uom_id,
            metadata: $dto->metadata,
        );

        return $this->comboItemRepository->save($comboItem);
    }
}
