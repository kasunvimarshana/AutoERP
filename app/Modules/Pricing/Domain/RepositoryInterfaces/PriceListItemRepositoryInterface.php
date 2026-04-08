<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Pricing\Domain\Entities\PriceListItem;

interface PriceListItemRepositoryInterface extends RepositoryInterface
{
    public function save(PriceListItem $priceListItem): PriceListItem;

    public function findById(int $id): ?PriceListItem;

    public function findByPriceList(int $priceListId): array;

    public function findByProduct(int $tenantId, int $productId): array;

    public function list(array $filters, int $perPage, int $page): LengthAwarePaginator;
}
