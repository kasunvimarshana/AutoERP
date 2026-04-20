<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Customer\Application\Contracts\FindCustomerContactServiceInterface;
use Modules\Customer\Domain\Entities\CustomerContact;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;

class FindCustomerContactService implements FindCustomerContactServiceInterface
{
    public function __construct(private readonly CustomerContactRepositoryInterface $customerContactRepository) {}

    public function find(mixed $id): ?CustomerContact
    {
        return $this->customerContactRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->customerContactRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'customer_id', 'email', 'is_primary'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'name', 'email', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateByCustomer(int $tenantId, int $customerId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->customerContactRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
