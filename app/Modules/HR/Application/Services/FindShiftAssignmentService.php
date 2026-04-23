<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindShiftAssignmentServiceInterface;
use Modules\HR\Domain\Entities\ShiftAssignment;
use Modules\HR\Domain\RepositoryInterfaces\ShiftAssignmentRepositoryInterface;

class FindShiftAssignmentService implements FindShiftAssignmentServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'employee_id', 'shift_id'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'employee_id', 'shift_id', 'effective_from', 'effective_to', 'created_at'];

    public function __construct(
        private readonly ShiftAssignmentRepositoryInterface $assignmentRepository,
    ) {}

    public function find(mixed $id): ?ShiftAssignment
    {
        return $this->assignmentRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->assignmentRepository->resetCriteria();

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
