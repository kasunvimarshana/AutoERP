<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindPerformanceCycleServiceInterface;
use Modules\HR\Domain\Entities\PerformanceCycle;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceCycleRepositoryInterface;

class FindPerformanceCycleService implements FindPerformanceCycleServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'is_active'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'period_start', 'period_end', 'is_active', 'created_at'];

    public function __construct(
        private readonly PerformanceCycleRepositoryInterface $cycleRepository,
    ) {}

    public function find(mixed $id): ?PerformanceCycle
    {
        return $this->cycleRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->cycleRepository->resetCriteria();

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
