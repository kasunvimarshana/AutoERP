<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Workflow\Application\DTOs\CreateWorkflowDTO;
use Modules\Workflow\Application\DTOs\CreateWorkflowInstanceDTO;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryContract;
use Modules\Workflow\Domain\Entities\WorkflowInstance;
use Modules\Workflow\Domain\Entities\WorkflowTransitionLog;

/**
 * Workflow service.
 *
 * Orchestrates all workflow definition use cases.
 * All mutations are wrapped in DB::transaction to ensure atomicity.
 * No business logic in controllers — everything is delegated here.
 */
class WorkflowService implements ServiceContract
{
    public function __construct(
        private readonly WorkflowRepositoryContract $repository,
    ) {}

    /**
     * Return a paginated list of workflow definitions for the current tenant.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Create a new workflow definition.
     */
    public function create(CreateWorkflowDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->repository->create([
                'name'        => $dto->name,
                'entity_type' => $dto->entityType,
                'description' => $dto->description,
                'is_active'   => $dto->isActive,
            ]);
        });
    }

    /**
     * Show a single workflow definition by ID.
     */
    public function show(int|string $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Update an existing workflow definition.
     *
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->repository->update($id, $data);
        });
    }

    /**
     * Delete a workflow definition.
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }

    /**
     * Return all workflow definitions of a given entity type (tenant-scoped).
     */
    public function findByEntityType(string $entityType): Collection
    {
        return $this->repository->findByEntityType($entityType);
    }

    /**
     * Create a new workflow instance for an entity.
     *
     * The instance is started immediately with the given initial state (if provided).
     */
    public function createInstance(CreateWorkflowInstanceDTO $dto): WorkflowInstance
    {
        return DB::transaction(function () use ($dto): WorkflowInstance {
            /** @var WorkflowInstance $instance */
            $instance = WorkflowInstance::create([
                'workflow_definition_id' => $dto->workflowDefinitionId,
                'entity_type'            => $dto->entityType,
                'entity_id'              => $dto->entityId,
                'current_state_id'       => $dto->initialStateId,
                'started_at'             => now(),
            ]);

            return $instance;
        });
    }

    /**
     * List all workflow instances for a given entity type (tenant-scoped).
     */
    public function listInstances(string $entityType): Collection
    {
        return WorkflowInstance::query()
            ->where('entity_type', $entityType)
            ->get();
    }

    /**
     * Apply a transition to a workflow instance, moving it to a new state.
     *
     * Records the transition in the transition log for audit purposes.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function applyTransition(int $instanceId, int $toStateId, ?string $comment = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $toStateId, $comment): WorkflowInstance {
            /** @var WorkflowInstance $instance */
            $instance = WorkflowInstance::findOrFail($instanceId);

            $fromStateId = $instance->current_state_id;

            $instance->update(['current_state_id' => $toStateId]);

            WorkflowTransitionLog::create([
                'workflow_instance_id' => $instance->id,
                'from_state_id'        => $fromStateId,
                'to_state_id'          => $toStateId,
                'event_name'           => 'manual_transition',
                'transitioned_at'      => now(),
                'notes'                => $comment,
            ]);

            return $instance->fresh();
        });
    }

    /**
     * Show a single workflow definition by ID.
     */
    public function showDefinition(int|string $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Show a single workflow instance by ID.
     */
    public function showInstance(int|string $id): WorkflowInstance
    {
        return WorkflowInstance::findOrFail($id);
    }

    /**
     * Delete a workflow definition.
     */
    public function deleteDefinition(int|string $id): bool
    {
        return DB::transaction(fn (): bool => $this->repository->delete($id));
    }

    /**
     * Return the transition log for a given workflow instance.
     *
     * @return Collection<int, WorkflowTransitionLog>
     */
    public function listTransitionLogs(int $instanceId): Collection
    {
        return WorkflowTransitionLog::query()
            ->where('workflow_instance_id', $instanceId)
            ->orderBy('transitioned_at')
            ->get();
    }
}
