<?php

declare(strict_types=1);

namespace Modules\Workflow\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Workflow\Enums\WorkflowStatus;
use Modules\Workflow\Models\Workflow;

class WorkflowRepository extends BaseRepository
{
    public function __construct(
        private Workflow $model
    ) {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Workflow::class;
    }

    public function find(int $id): ?Workflow
    {
        return $this->model->with(['steps.conditions', 'creator'])->find($id);
    }

    public function findByCode(string $code): ?Workflow
    {
        return $this->model->where('code', $code)->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['creator', 'updater']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['is_template'])) {
            $query->where('is_template', $filters['is_template']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return $this->model
            ->where('status', WorkflowStatus::ACTIVE)
            ->with(['steps.conditions'])
            ->get();
    }

    public function getByTriggerType(string $triggerType): Collection
    {
        return $this->model
            ->where('status', WorkflowStatus::ACTIVE)
            ->where('trigger_type', $triggerType)
            ->with(['steps.conditions'])
            ->get();
    }

    public function create(array $data): Workflow
    {
        return $this->model->create($data);
    }

    public function updateWorkflow(Workflow $workflow, array $data): Workflow
    {
        $workflow->update($data);

        return $workflow->fresh(['steps.conditions']);
    }

    public function deleteWorkflow(Workflow $workflow): bool
    {
        return $workflow->delete();
    }

    public function duplicate(Workflow $workflow, array $overrides = []): Workflow
    {
        $data = array_merge(
            $workflow->only(['name', 'description', 'trigger_type', 'trigger_config', 'entity_type', 'metadata']),
            $overrides,
            ['status' => WorkflowStatus::DRAFT, 'version' => 1]
        );

        $newWorkflow = $this->create($data);

        foreach ($workflow->steps as $step) {
            $newWorkflow->steps()->create($step->only([
                'name', 'description', 'type', 'sequence', 'config',
                'action_config', 'approval_config', 'condition_config',
                'timeout_seconds', 'retry_count', 'is_required', 'metadata',
            ]));
        }

        return $newWorkflow->fresh(['steps.conditions']);
    }
}
