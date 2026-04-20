<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\FindCustomerPriceListServiceInterface;
use Modules\Pricing\Domain\Entities\CustomerPriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\CustomerPriceListRepositoryInterface;

class FindCustomerPriceListService implements FindCustomerPriceListServiceInterface
{
    public function __construct(private readonly CustomerPriceListRepositoryInterface $customerPriceListRepository) {}

    public function find(mixed $id): ?CustomerPriceList
    {
        return $this->customerPriceListRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->customerPriceListRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'customer_id', 'price_list_id'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'priority', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateByCustomer(int $tenantId, int $customerId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->customerPriceListRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderBy('priority', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
