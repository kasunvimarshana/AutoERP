<?php

namespace Modules\ProjectManagement\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\ProjectManagement\Domain\Contracts\MilestoneRepositoryInterface;
use Modules\ProjectManagement\Infrastructure\Models\MilestoneModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class MilestoneRepository extends BaseEloquentRepository implements MilestoneRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new MilestoneModel());
    }

    public function findByProject(string $projectId): Collection
    {
        return MilestoneModel::where('project_id', $projectId)
            ->orderBy('due_date')
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = MilestoneModel::query();

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('due_date')->paginate($perPage);
    }
}
