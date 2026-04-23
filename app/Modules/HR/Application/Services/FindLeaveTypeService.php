<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindLeaveTypeServiceInterface;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;

class FindLeaveTypeService implements FindLeaveTypeServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'code', 'is_active', 'is_paid'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'code', 'max_days_per_year', 'is_active', 'created_at'];

    public function __construct(
        private readonly LeaveTypeRepositoryInterface $leaveTypeRepository,
    ) {}

    public function find(mixed $id): ?LeaveType
    {
        return $this->leaveTypeRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->leaveTypeRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    /** @return array{0: string|null, 1: string} */
    private function parseSort(?string $sort): array
    {
        if ($sort === null || $sort === '') {
            return [null, 'asc'];
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        return in_array($field, self::ALLOWED_SORTS, true) ? [$field, $direction] : [null, 'asc'];
    }
}
