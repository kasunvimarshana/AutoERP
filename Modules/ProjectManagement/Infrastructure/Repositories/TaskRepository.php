<?php

namespace Modules\ProjectManagement\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\ProjectManagement\Domain\Contracts\TaskRepositoryInterface;
use Modules\ProjectManagement\Infrastructure\Models\TaskModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class TaskRepository extends BaseEloquentRepository implements TaskRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TaskModel());
    }

    public function findByProject(string $projectId): Collection
    {
        return TaskModel::where('project_id', $projectId)->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = TaskModel::query();

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
