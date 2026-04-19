<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitUserRepositoryInterface;

class FindOrganizationUnitUserService implements FindOrganizationUnitUserServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'org_unit_id', 'user_id', 'role', 'is_primary'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'tenant_id', 'org_unit_id', 'user_id', 'role', 'is_primary', 'created_at', 'updated_at'];

    public function __construct(private readonly OrganizationUnitUserRepositoryInterface $organizationUnitUserRepository)
    {
    }

    public function find(int $id): ?OrganizationUnitUser
    {
        return $this->organizationUnitUserRepository->find($id);
    }

    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator
    {
        $repository = $this->organizationUnitUserRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repository->where($field, $value);
            }
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
}
