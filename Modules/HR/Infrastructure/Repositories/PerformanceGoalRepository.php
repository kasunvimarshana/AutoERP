<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\PerformanceGoalRepositoryInterface;
use Modules\HR\Infrastructure\Models\PerformanceGoalModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class PerformanceGoalRepository extends BaseEloquentRepository implements PerformanceGoalRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new PerformanceGoalModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = PerformanceGoalModel::query();

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
