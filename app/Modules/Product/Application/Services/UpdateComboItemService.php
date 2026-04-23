<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Application\DTOs\ComboItemData;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\Exceptions\ComboItemNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;

class UpdateComboItemService extends BaseService implements UpdateComboItemServiceInterface
{
    public function __construct(private readonly ComboItemRepositoryInterface $comboItemRepository)
    {
        parent::__construct($comboItemRepository);
    }

    protected function handle(array $data): ComboItem
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->comboItemRepository->find($id);

        if (! $entity) {
            throw new ComboItemNotFoundException($id);
        }

        $dto = ComboItemData::fromArray($data);
        $entity->update(
            quantity: $dto->quantity,
            uomId: $dto->uom_id,
            componentVariantId: $dto->component_variant_id,
            metadata: $dto->metadata,
            sortOrder: $dto->sort_order,
            isOptional: $dto->is_optional,
            notes: $dto->notes,
        );

        return $this->comboItemRepository->save($entity);
    }
}
