<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Customer\Application\Contracts\FindCustomerServiceInterface;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;

class FindCustomerService implements FindCustomerServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = [
        'tenant_id',
        'user_id',
        'org_unit_id',
        'customer_code',
        'name',
        'type',
        'status',
        'currency_id',
        'ar_account_id',
    ];

    /** @var array<string> */
    private const ALLOWED_SORTS = [
        'id',
        'customer_code',
        'name',
        'type',
        'status',
        'credit_limit',
        'payment_terms_days',
        'created_at',
        'updated_at',
    ];

    /** @var array<string> */
    private const ALLOWED_INCLUDES = ['user', 'orgUnit', 'currency', 'arAccount', 'addresses', 'contacts'];

    public function __construct(private readonly CustomerRepositoryInterface $customerRepository) {}

    public function find(mixed $id): ?Customer
    {
        return $this->customerRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): LengthAwarePaginator {
        $repository = $this->customerRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if (is_string($value) && in_array($field, ['name', 'customer_code'], true)) {
                $repository->where($field, 'like', '%'.$value.'%');

                continue;
            }

            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        $relations = $this->parseIncludes($include);
        if ($relations !== []) {
            $repository->with($relations);
        }

        $perPage = $perPage ?? 15;

        return $repository->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function parseSort(?string $sort): array
    {
        if ($sort === null) {
            return [null, 'asc'];
        }

        $sort = trim($sort);

        if ($sort === '') {
            return [null, 'asc'];
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (! in_array($field, self::ALLOWED_SORTS, true)) {
            return [null, 'asc'];
        }

        return [$field, $direction];
    }

    /**
     * @return list<string>
     */
    private function parseIncludes(?string $include): array
    {
        if ($include === null) {
            return [];
        }

        $relations = array_filter(array_map('trim', explode(',', $include)));

        if ($relations === []) {
            return [];
        }

        return array_values(array_filter(
            $relations,
            fn (string $relation): bool => in_array($relation, self::ALLOWED_INCLUDES, true)
        ));
    }
}
