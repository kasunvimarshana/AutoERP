<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class FindSupplierService implements FindSupplierServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = [
        'tenant_id',
        'user_id',
        'org_unit_id',
        'supplier_code',
        'name',
        'type',
        'status',
        'currency_id',
        'ap_account_id',
    ];

    /** @var array<string> */
    private const ALLOWED_SORTS = [
        'id',
        'supplier_code',
        'name',
        'type',
        'status',
        'payment_terms_days',
        'created_at',
        'updated_at',
    ];

    /** @var array<string> */
    private const ALLOWED_INCLUDES = ['user', 'orgUnit', 'currency', 'apAccount', 'addresses', 'contacts'];

    public function __construct(private readonly SupplierRepositoryInterface $supplierRepository) {}

    public function find(mixed $id): ?Supplier
    {
        return $this->supplierRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): LengthAwarePaginator {
        $repository = $this->supplierRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if (is_string($value) && in_array($field, ['name', 'supplier_code'], true)) {
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
