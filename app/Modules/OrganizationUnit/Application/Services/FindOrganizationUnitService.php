<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class FindOrganizationUnitService implements FindOrganizationUnitServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'type_id', 'parent_id', 'manager_user_id', 'name', 'code', 'is_active'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'tenant_id', 'type_id', 'parent_id', 'name', 'code', 'is_active', 'created_at', 'updated_at'];

    /** @var array<string> */
    private const ALLOWED_INCLUDES = ['attachments'];

    public function __construct(private readonly OrganizationUnitRepositoryInterface $organizationUnitRepository)
    {
    }

    public function find(int $id): ?OrganizationUnit
    {
        return $this->organizationUnitRepository->find($id);
    }

    public function list(array $filters, int $perPage, int $page, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->organizationUnitRepository->resetCriteria();

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
