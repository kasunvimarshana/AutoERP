<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Pricing\Domain\Entities\PriceListItem;

interface PriceListItemRepositoryInterface extends RepositoryInterface
{
    public function save(PriceListItem $priceListItem): PriceListItem;

    /**
     * @return array<int, int>
     */
    public function getDistinctProductIdsByPriceList(int $tenantId, int $priceListId): array;

    public function findBestMatch(
        int $tenantId,
        string $type,
        int $productId,
        ?int $variantId,
        int $uomId,
        string $quantity,
        int $currencyId,
        ?int $customerId,
        ?int $supplierId,
        \DateTimeInterface $priceDate,
    ): ?array;

    public function find(int|string $id, array $columns = ['*']): ?PriceListItem;
}
