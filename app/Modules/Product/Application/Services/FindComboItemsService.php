<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Collection;
use Modules\Product\Application\Contracts\FindComboItemsServiceInterface;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;

class FindComboItemsService implements FindComboItemsServiceInterface
{
    public function __construct(
        private readonly ComboItemRepositoryInterface $comboItemRepository
    ) {}

    public function findByProduct(int $productId): Collection
    {
        return $this->comboItemRepository->findByProduct($productId);
    }

    public function find(int $itemId): ?ComboItem
    {
        return $this->comboItemRepository->find($itemId);
    }
}
