<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Workflow\Domain\Contracts\WorkflowInstanceRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowInstance;
use Modules\Workflow\Domain\Entities\WorkflowInstanceLog;
use Modules\Workflow\Infrastructure\Models\WorkflowInstanceLogModel;
use Modules\Workflow\Infrastructure\Models\WorkflowInstanceModel;

class WorkflowInstanceRepository extends BaseRepository implements WorkflowInstanceRepositoryInterface
{
    protected function model(): string
    {
        return WorkflowInstanceModel::class;
    }

    public function findById(int $id, int $tenantId): ?WorkflowInstance
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (WorkflowInstanceModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findByEntity(string $entityType, int $entityId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (WorkflowInstanceModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(WorkflowInstance $entity): WorkflowInstance
    {
        if ($entity->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $entity->tenantId)
                ->findOrFail($entity->id);
        } else {
            $model = new WorkflowInstanceModel;
            $model->tenant_id = $entity->tenantId;
        }

        $model->workflow_definition_id = $entity->workflowDefinitionId;
        $model->entity_type = $entity->entityType;
        $model->entity_id = $entity->entityId;
        $model->current_state_id = $entity->currentStateId;
        $model->status = $entity->status;
        $model->started_at = $entity->startedAt;
        $model->completed_at = $entity->completedAt;
        $model->started_by_user_id = $entity->startedByUserId;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    public function findLogs(int $instanceId, int $tenantId): array
    {
        return WorkflowInstanceLogModel::where('workflow_instance_id', $instanceId)
            ->where('tenant_id', $tenantId)
            ->orderBy('acted_at')
            ->get()
            ->map(fn (WorkflowInstanceLogModel $m) => $this->toLogDomain($m))
            ->all();
    }

    public function saveLog(WorkflowInstanceLog $log): WorkflowInstanceLog
    {
        $model = new WorkflowInstanceLogModel;
        $model->workflow_instance_id = $log->workflowInstanceId;
        $model->tenant_id = $log->tenantId;
        $model->from_state_id = $log->fromStateId;
        $model->to_state_id = $log->toStateId;
        $model->transition_id = $log->transitionId;
        $model->comment = $log->comment;
        $model->actor_user_id = $log->actorUserId;
        $model->acted_at = $log->actedAt;
        $model->created_at = now();
        $model->save();

        return $this->toLogDomain($model);
    }

    private function toDomain(WorkflowInstanceModel $model): WorkflowInstance
    {
        return new WorkflowInstance(
            id: $model->id,
            tenantId: $model->tenant_id,
            workflowDefinitionId: $model->workflow_definition_id,
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            currentStateId: $model->current_state_id,
            status: $model->status,
            startedAt: $model->started_at?->toIso8601String(),
            completedAt: $model->completed_at?->toIso8601String(),
            startedByUserId: $model->started_by_user_id,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toLogDomain(WorkflowInstanceLogModel $model): WorkflowInstanceLog
    {
        return new WorkflowInstanceLog(
            id: $model->id,
            workflowInstanceId: $model->workflow_instance_id,
            tenantId: $model->tenant_id,
            fromStateId: $model->from_state_id,
            toStateId: $model->to_state_id,
            transitionId: $model->transition_id,
            comment: $model->comment,
            actorUserId: $model->actor_user_id,
            actedAt: $model->acted_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
