<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class ListPerformanceReviews
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $repo) {}

    public function execute(array $filters = [], int $perPage = 15, int $page = 1, ?string $sort = null): LengthAwarePaginator
    {
        $repo = clone $this->repo;

        foreach ($filters as $field => $value) {
            $repo->where($field, $value);
        }

        if ($sort) {
            [$column, $direction] = explode(':', $sort) + [1 => 'asc'];
            $repo->orderBy($column, $direction);
        }

        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
