<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class FindLeaveRequestService implements FindLeaveRequestServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'employee_id', 'leave_type_id', 'status'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'start_date', 'end_date', 'total_days', 'status', 'created_at'];

    public function __construct(
        private readonly LeaveRequestRepositoryInterface $requestRepository,
    ) {}

    public function find(mixed $id): ?LeaveRequest
    {
        return $this->requestRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->requestRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        if (isset($filters['start_date_from'])) {
            $repository->where('start_date', '>=', $filters['start_date_from']);
        }

        if (isset($filters['start_date_to'])) {
            $repository->where('start_date', '<=', $filters['start_date_to']);
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
