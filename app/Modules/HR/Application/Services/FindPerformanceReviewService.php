<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class FindPerformanceReviewService implements FindPerformanceReviewServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'employee_id', 'cycle_id', 'status', 'reviewer_id'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'employee_id', 'cycle_id', 'status', 'created_at', 'updated_at'];

    public function __construct(
        private readonly PerformanceReviewRepositoryInterface $reviewRepository,
    ) {}

    public function find(mixed $id): ?PerformanceReview
    {
        return $this->reviewRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->reviewRepository->resetCriteria();

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
