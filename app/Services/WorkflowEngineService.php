<?php

namespace App\Services;

use App\Contracts\Services\WorkflowEngineInterface;
use App\Enums\AuditAction;
use App\Enums\WorkflowInstanceStatus;
use App\Events\WorkflowTransitioned;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowHistory;
use App\Models\WorkflowInstance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class WorkflowEngineService implements WorkflowEngineInterface
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WorkflowDefinition::where('tenant_id', $tenantId)
            ->with(['states', 'transitions']);

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createDefinition(array $data): WorkflowDefinition
    {
        return DB::transaction(function () use ($data) {
            $states = $data['states'] ?? [];
            $transitions = $data['transitions'] ?? [];
            unset($data['states'], $data['transitions']);

            $definition = WorkflowDefinition::create($data);

            $stateMap = [];
            foreach ($states as $stateData) {
                $stateData['tenant_id'] = $data['tenant_id'];
                $stateData['workflow_definition_id'] = $definition->id;
                $state = $definition->states()->create($stateData);
                $stateMap[$stateData['name']] = $state->id;
            }

            foreach ($transitions as $transData) {
                $transData['tenant_id'] = $data['tenant_id'];
                $transData['workflow_definition_id'] = $definition->id;
                // Allow referencing states by name string
                if (isset($transData['from_state_name'])) {
                    $transData['from_state_id'] = $stateMap[$transData['from_state_name']] ?? $transData['from_state_id'];
                    unset($transData['from_state_name']);
                }
                if (isset($transData['to_state_name'])) {
                    $transData['to_state_id'] = $stateMap[$transData['to_state_name']] ?? $transData['to_state_id'];
                    unset($transData['to_state_name']);
                }
                $definition->transitions()->create($transData);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: WorkflowDefinition::class,
                auditableId: $definition->id,
                newValues: $definition->toArray(),
            );

            return $definition->load(['states', 'transitions']);
        });
    }

    public function updateDefinition(string $id, array $data): WorkflowDefinition
    {
        return DB::transaction(function () use ($id, $data) {
            $definition = WorkflowDefinition::findOrFail($id);
            $old = $definition->toArray();
            $definition->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: WorkflowDefinition::class,
                auditableId: $definition->id,
                oldValues: $old,
                newValues: $definition->fresh()->toArray(),
            );

            return $definition->load(['states', 'transitions']);
        });
    }

    public function startInstance(
        string $definitionId,
        string $entityType,
        string $entityId,
        string $tenantId,
        ?string $userId = null,
        array $context = []
    ): WorkflowInstance {
        return DB::transaction(function () use ($definitionId, $entityType, $entityId, $tenantId, $userId, $context) {
            $definition = WorkflowDefinition::where('id', $definitionId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->firstOrFail();

            $initialState = $definition->initialState();
            if (! $initialState) {
                throw ValidationException::withMessages([
                    'workflow_definition_id' => ['Workflow definition has no initial state.'],
                ]);
            }

            $instance = WorkflowInstance::create([
                'tenant_id' => $tenantId,
                'workflow_definition_id' => $definitionId,
                'current_state_id' => $initialState->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'status' => WorkflowInstanceStatus::Active,
                'initiated_by' => $userId,
                'context' => $context,
            ]);

            WorkflowHistory::create([
                'tenant_id' => $tenantId,
                'workflow_instance_id' => $instance->id,
                'transition_id' => null,
                'from_state_id' => null,
                'to_state_id' => $initialState->id,
                'transitioned_by' => $userId,
                'comment' => 'Instance started.',
                'context' => $context,
                'transitioned_at' => now(),
            ]);

            return $instance->load(['currentState', 'workflowDefinition']);
        });
    }

    public function transition(
        string $instanceId,
        string $transitionId,
        string $tenantId,
        ?string $userId = null,
        ?string $comment = null
    ): WorkflowInstance {
        return DB::transaction(function () use ($instanceId, $transitionId, $tenantId, $userId, $comment) {
            $instance = WorkflowInstance::where('id', $instanceId)
                ->where('tenant_id', $tenantId)
                ->where('status', WorkflowInstanceStatus::Active)
                ->lockForUpdate()
                ->firstOrFail();

            $transition = $instance->workflowDefinition
                ->transitions()
                ->where('id', $transitionId)
                ->where('from_state_id', $instance->current_state_id)
                ->firstOrFail();

            $fromStateId = $instance->current_state_id;
            $instance->current_state_id = $transition->to_state_id;

            // Auto-complete if target state is final
            if ($transition->toState->is_final) {
                $instance->status = WorkflowInstanceStatus::Completed;
                $instance->completed_at = now();
            }

            $instance->save();

            WorkflowHistory::create([
                'tenant_id' => $tenantId,
                'workflow_instance_id' => $instance->id,
                'transition_id' => $transition->id,
                'from_state_id' => $fromStateId,
                'to_state_id' => $transition->to_state_id,
                'transitioned_by' => $userId,
                'comment' => $comment,
                'transitioned_at' => now(),
            ]);

            Event::dispatch(new WorkflowTransitioned(
                instance: $instance,
                transition: $transition,
                fromStateId: $fromStateId,
                toStateId: $transition->to_state_id,
                transitionedBy: $userId,
                comment: $comment,
            ));

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: WorkflowInstance::class,
                auditableId: $instance->id,
                oldValues: ['state' => $fromStateId],
                newValues: ['state' => $transition->to_state_id],
            );

            return $instance->load(['currentState', 'workflowDefinition', 'history']);
        });
    }

    public function cancelInstance(
        string $instanceId,
        string $tenantId,
        ?string $userId = null
    ): WorkflowInstance {
        return DB::transaction(function () use ($instanceId, $tenantId, $userId) {
            $instance = WorkflowInstance::where('id', $instanceId)
                ->where('tenant_id', $tenantId)
                ->where('status', WorkflowInstanceStatus::Active)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStateId = $instance->current_state_id;
            $instance->status = WorkflowInstanceStatus::Cancelled;
            $instance->completed_at = now();
            $instance->save();

            WorkflowHistory::create([
                'tenant_id' => $tenantId,
                'workflow_instance_id' => $instance->id,
                'transition_id' => null,
                'from_state_id' => $fromStateId,
                'to_state_id' => $fromStateId,
                'transitioned_by' => $userId,
                'comment' => 'Instance cancelled.',
                'transitioned_at' => now(),
            ]);

            $this->auditService->log(
                action: AuditAction::Deleted,
                auditableType: WorkflowInstance::class,
                auditableId: $instance->id,
                oldValues: ['status' => WorkflowInstanceStatus::Active->value],
                newValues: ['status' => WorkflowInstanceStatus::Cancelled->value],
            );

            return $instance->load(['currentState', 'workflowDefinition']);
        });
    }

    public function getInstance(string $entityType, string $entityId, string $tenantId): ?WorkflowInstance
    {
        return WorkflowInstance::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('tenant_id', $tenantId)
            ->with(['currentState', 'workflowDefinition', 'history'])
            ->latest()
            ->first();
    }
}
