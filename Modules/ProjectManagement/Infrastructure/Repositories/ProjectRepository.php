<?php

namespace Modules\ProjectManagement\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\ProjectManagement\Domain\Contracts\ProjectRepositoryInterface;
use Modules\ProjectManagement\Infrastructure\Models\ProjectModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class ProjectRepository extends BaseEloquentRepository implements ProjectRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new ProjectModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = ProjectModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByStatus(string $tenantId, string $status): Collection
    {
        return ProjectModel::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get();
    }
}
