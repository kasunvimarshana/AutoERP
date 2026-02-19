<?php

declare(strict_types=1);

namespace Modules\Workflow\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Workflow\Models\WorkflowStep;

class WorkflowStepRepository extends BaseRepository
{
    public function __construct(
        private WorkflowStep $model
    ) {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return WorkflowStep::class;
    }

    public function find(int $id): ?WorkflowStep
    {
        return $this->model->with(['workflow', 'conditions.nextStep'])->find($id);
    }

    public function getByWorkflow(int $workflowId): Collection
    {
        return $this->model
            ->where('workflow_id', $workflowId)
            ->with(['conditions.nextStep'])
            ->orderBy('sequence')
            ->get();
    }

    public function create(array $data): WorkflowStep
    {
        return $this->model->create($data);
    }

    public function updateStep(WorkflowStep $step, array $data): WorkflowStep
    {
        $step->update($data);

        return $step->fresh(['conditions.nextStep']);
    }

    public function deleteStep(WorkflowStep $step): bool
    {
        return $step->delete();
    }

    public function reorder(int $workflowId, array $stepIds): void
    {
        foreach ($stepIds as $sequence => $stepId) {
            $this->model
                ->where('id', $stepId)
                ->where('workflow_id', $workflowId)
                ->update(['sequence' => $sequence + 1]);
        }
    }
}
