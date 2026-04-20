<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class FindPriceListService implements FindPriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $priceListRepository) {}

    public function find(mixed $id): ?PriceList
    {
        return $this->priceListRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->priceListRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'type', 'currency_id', 'is_default', 'is_active'], true)) {
                $repository->where($field, $value);
            }

            if ($field === 'name' && is_string($value) && $value !== '') {
                $repository->where('name', 'like', '%'.$value.'%');
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'name', 'type', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }
}
