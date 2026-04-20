<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Customer\Application\Contracts\FindCustomerAddressServiceInterface;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;

class FindCustomerAddressService implements FindCustomerAddressServiceInterface
{
    public function __construct(private readonly CustomerAddressRepositoryInterface $customerAddressRepository) {}

    public function find(mixed $id): ?CustomerAddress
    {
        return $this->customerAddressRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->customerAddressRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'customer_id', 'type', 'country_id', 'is_default'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'type', 'city', 'postal_code', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateByCustomer(int $tenantId, int $customerId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->customerAddressRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
