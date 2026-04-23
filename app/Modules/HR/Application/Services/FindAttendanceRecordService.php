<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindAttendanceRecordServiceInterface;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRecordRepositoryInterface;

class FindAttendanceRecordService implements FindAttendanceRecordServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'employee_id', 'shift_id', 'status'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'attendance_date', 'status', 'worked_minutes', 'created_at'];

    public function __construct(
        private readonly AttendanceRecordRepositoryInterface $recordRepository,
    ) {}

    public function find(mixed $id): ?AttendanceRecord
    {
        return $this->recordRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->recordRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        if (isset($filters['date'])) {
            $repository->where('attendance_date', $filters['date']);
        }

        if (isset($filters['year']) && isset($filters['month'])) {
            $yearMonth = sprintf('%04d-%02d', (int) $filters['year'], (int) $filters['month']);
            $repository->where('attendance_date', 'like', $yearMonth.'%');
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
