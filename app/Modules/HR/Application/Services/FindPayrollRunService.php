<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindPayrollRunServiceInterface;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRunRepositoryInterface;

class FindPayrollRunService implements FindPayrollRunServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'status'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'period_start', 'period_end', 'status', 'total_net', 'created_at'];

    public function __construct(
        private readonly PayrollRunRepositoryInterface $runRepository,
    ) {}

    public function find(mixed $id): ?PayrollRun
    {
        return $this->runRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->runRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        if (isset($filters['year']) && isset($filters['month'])) {
            $yearMonth = sprintf('%04d-%02d', (int) $filters['year'], (int) $filters['month']);
            $repository->where('period_start', 'like', $yearMonth.'%');
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
