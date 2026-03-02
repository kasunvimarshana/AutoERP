<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Illuminate\Support\Facades\DB;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowDefinition;
use Modules\Workflow\Domain\Entities\WorkflowState;
use Modules\Workflow\Domain\Entities\WorkflowTransition;
use Modules\Workflow\Infrastructure\Models\WorkflowDefinitionModel;
use Modules\Workflow\Infrastructure\Models\WorkflowStateModel;
use Modules\Workflow\Infrastructure\Models\WorkflowTransitionModel;

class WorkflowDefinitionRepository extends BaseRepository implements WorkflowDefinitionRepositoryInterface
{
    protected function model(): string
    {
        return WorkflowDefinitionModel::class;
    }

    public function findById(int $id, int $tenantId): ?WorkflowDefinition
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
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (WorkflowDefinitionModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(WorkflowDefinition $entity, array $states = [], array $transitions = []): WorkflowDefinition
    {
        return DB::transaction(function () use ($entity, $states, $transitions): WorkflowDefinition {
            if ($entity->id !== null) {
                $model = $this->newQuery()
                    ->where('tenant_id', $entity->tenantId)
                    ->findOrFail($entity->id);
            } else {
                $model = new WorkflowDefinitionModel;
                $model->tenant_id = $entity->tenantId;
            }

            $model->name = $entity->name;
            $model->description = $entity->description;
            $model->entity_type = $entity->entityType;
            $model->status = $entity->status;
            $model->is_active = $entity->isActive;
            $model->save();

            if ($states !== []) {
                WorkflowStateModel::where('workflow_definition_id', $model->id)->delete();
                WorkflowTransitionModel::where('workflow_definition_id', $model->id)->delete();

                $nameToId = [];
                foreach ($states as $stateData) {
                    $stateModel = new WorkflowStateModel;
                    $stateModel->workflow_definition_id = $model->id;
                    $stateModel->tenant_id = $entity->tenantId;
                    $stateModel->name = $stateData['name'];
                    $stateModel->description = $stateData['description'] ?? null;
                    $stateModel->is_initial = (bool) ($stateData['is_initial'] ?? false);
                    $stateModel->is_final = (bool) ($stateData['is_final'] ?? false);
                    $stateModel->sort_order = (int) ($stateData['sort_order'] ?? 0);
                    $stateModel->save();
                    $nameToId[$stateData['name']] = $stateModel->id;
                }

                foreach ($transitions as $transData) {
                    $fromId = $nameToId[$transData['from_state_name']] ?? null;
                    $toId = $nameToId[$transData['to_state_name']] ?? null;

                    if ($fromId === null || $toId === null) {
                        throw new \DomainException(
                            "Transition '{$transData['name']}' references unknown state(s)."
                        );
                    }

                    $transModel = new WorkflowTransitionModel;
                    $transModel->workflow_definition_id = $model->id;
                    $transModel->from_state_id = $fromId;
                    $transModel->to_state_id = $toId;
                    $transModel->tenant_id = $entity->tenantId;
                    $transModel->name = $transData['name'];
                    $transModel->description = $transData['description'] ?? null;
                    $transModel->requires_comment = (bool) ($transData['requires_comment'] ?? false);
                    $transModel->save();
                }
            }

            return $this->toDomain($model);
        });
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    public function findStates(int $definitionId, int $tenantId): array
    {
        return WorkflowStateModel::where('workflow_definition_id', $definitionId)
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (WorkflowStateModel $m) => $this->toStateDomain($m))
            ->all();
    }

    public function findTransitions(int $definitionId, int $tenantId): array
    {
        return WorkflowTransitionModel::where('workflow_definition_id', $definitionId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (WorkflowTransitionModel $m) => $this->toTransitionDomain($m))
            ->all();
    }

    private function toDomain(WorkflowDefinitionModel $model): WorkflowDefinition
    {
        return new WorkflowDefinition(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            entityType: $model->entity_type,
            status: $model->status,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toStateDomain(WorkflowStateModel $model): WorkflowState
    {
        return new WorkflowState(
            id: $model->id,
            workflowDefinitionId: $model->workflow_definition_id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            isInitial: (bool) $model->is_initial,
            isFinal: (bool) $model->is_final,
            sortOrder: (int) $model->sort_order,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toTransitionDomain(WorkflowTransitionModel $model): WorkflowTransition
    {
        return new WorkflowTransition(
            id: $model->id,
            workflowDefinitionId: $model->workflow_definition_id,
            fromStateId: $model->from_state_id,
            toStateId: $model->to_state_id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            requiresComment: (bool) $model->requires_comment,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
