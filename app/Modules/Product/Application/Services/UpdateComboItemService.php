<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Product\Application\Contracts\UpdateComboItemServiceInterface;
use Modules\Product\Application\DTOs\ComboItemData;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\Events\ComboItemUpdated;
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
        $id        = $data['id'];
        $comboItem = $this->comboItemRepository->find($id);

        if (! $comboItem) {
            throw new ComboItemNotFoundException($id);
        }

        $dto = ComboItemData::fromArray($data);

        $priceOverride = null;
        if (isset($dto->price_override) && $dto->price_override !== null) {
            $priceOverride = new Money((float) $dto->price_override, $dto->currency ?? 'USD');
        }

        $comboItem->updateDetails(
            quantity:      $dto->quantity ?? $comboItem->getQuantity(),
            priceOverride: $priceOverride,
            sortOrder:     $dto->sort_order ?? $comboItem->getSortOrder(),
            metadata:      $dto->metadata,
        );

        $saved = $this->comboItemRepository->save($comboItem);

        $this->addEvent(new ComboItemUpdated($saved));

        return $saved;
    }
}
