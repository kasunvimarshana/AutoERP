<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindAttendanceLogServiceInterface;
use Modules\HR\Domain\Entities\AttendanceLog;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceLogRepositoryInterface;

class FindAttendanceLogService implements FindAttendanceLogServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'employee_id', 'biometric_device_id', 'punch_type', 'source'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'punch_time', 'punch_type', 'employee_id', 'created_at'];

    public function __construct(
        private readonly AttendanceLogRepositoryInterface $logRepository,
    ) {}

    public function find(mixed $id): ?AttendanceLog
    {
        return $this->logRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->logRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        if (isset($filters['date_from'])) {
            $repository->where('punch_time', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $repository->where('punch_time', '<=', $filters['date_to']);
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
