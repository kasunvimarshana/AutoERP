<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

/**
 * Delegates tenant read queries to the repository.
 *
 * Keeping query logic here (rather than in the controller) upholds DIP:
 * controllers depend on this service interface, not on the repository directly.
 */
class FindTenantService implements FindTenantServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['name', 'slug', 'domain', 'active', 'status'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'slug', 'domain', 'active', 'status', 'created_at', 'updated_at'];

    /** @var array<string> */
    private const ALLOWED_INCLUDES = ['attachments', 'tenantPlan', 'settingsItems'];

    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    public function find(int $id): ?Tenant
    {
        return $this->tenantRepository->find($id);
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return $this->tenantRepository->findByDomain($domain);
    }

    public function list(array $filters, int $perPage, int $page, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->tenantRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repository->where($field, $value);
            }
        }

        foreach ($this->parseIncludes($include) as $relation) {
            $repository->with($relation);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

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
     * @return array<int, string>
     */
    private function parseIncludes(?string $include): array
    {
        if ($include === null) {
            return [];
        }

        $include = trim($include);
        if ($include === '') {
            return [];
        }

        $relations = array_map('trim', explode(',', $include));

        return array_values(array_unique(array_filter(
            $relations,
            fn (string $relation): bool => in_array($relation, self::ALLOWED_INCLUDES, true)
        )));
    }
}
