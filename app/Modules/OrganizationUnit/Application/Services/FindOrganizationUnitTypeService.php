<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitTypeRepositoryInterface;

class FindOrganizationUnitTypeService implements FindOrganizationUnitTypeServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'level', 'is_active'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'tenant_id', 'name', 'level', 'is_active', 'created_at', 'updated_at'];

    public function __construct(private readonly OrganizationUnitTypeRepositoryInterface $organizationUnitTypeRepository) {}

    public function find(int $id): ?OrganizationUnitType
    {
        return $this->organizationUnitTypeRepository->find($id);
    }

    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator
    {
        $repository = $this->organizationUnitTypeRepository->resetCriteria();

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
