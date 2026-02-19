<?php

declare(strict_types=1);

namespace Modules\Workflow\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Workflow\Enums\InstanceStatus;
use Modules\Workflow\Models\WorkflowInstance;

class WorkflowInstanceRepository extends BaseRepository
{
    public function __construct(
        private WorkflowInstance $model
    ) {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return WorkflowInstance::class;
    }

    public function find(int $id): ?WorkflowInstance
    {
        return $this->model
            ->with(['workflow.steps', 'instanceSteps.step', 'approvals', 'starter'])
            ->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['workflow', 'starter', 'currentStep']);

        if (isset($filters['workflow_id'])) {
            $query->where('workflow_id', $filters['workflow_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (isset($filters['started_by'])) {
            $query->where('started_by', $filters['started_by']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return $this->model
            ->whereIn('status', [InstanceStatus::PENDING, InstanceStatus::RUNNING, InstanceStatus::WAITING])
            ->with(['workflow.steps', 'currentStep'])
            ->get();
    }

    public function getByEntity(string $entityType, int $entityId): Collection
    {
        return $this->model
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with(['workflow', 'instanceSteps.step'])
            ->get();
    }

    public function create(array $data): WorkflowInstance
    {
        return $this->model->create($data);
    }

    public function updateInstance(WorkflowInstance $instance, array $data): WorkflowInstance
    {
        $instance->update($data);

        return $instance->fresh(['workflow.steps', 'instanceSteps.step']);
    }

    public function deleteInstance(WorkflowInstance $instance): bool
    {
        return $instance->delete();
    }
}
