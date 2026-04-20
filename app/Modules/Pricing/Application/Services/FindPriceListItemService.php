<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\FindPriceListItemServiceInterface;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class FindPriceListItemService implements FindPriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $priceListItemRepository) {}

    public function find(mixed $id): ?PriceListItem
    {
        return $this->priceListItemRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->priceListItemRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'price_list_id', 'product_id', 'variant_id', 'uom_id'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'min_quantity', 'price', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateByPriceList(int $tenantId, int $priceListId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->priceListItemRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('price_list_id', $priceListId)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
