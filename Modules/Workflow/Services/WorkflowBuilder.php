<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Modules\Workflow\Enums\StepType;
use Modules\Workflow\Enums\WorkflowStatus;
use Modules\Workflow\Events\WorkflowCreated;
use Modules\Workflow\Events\WorkflowUpdated;
use Modules\Workflow\Exceptions\WorkflowBuilderException;
use Modules\Workflow\Models\Workflow;
use Modules\Workflow\Models\WorkflowStep;
use Modules\Workflow\Repositories\WorkflowRepository;
use Modules\Workflow\Repositories\WorkflowStepRepository;

class WorkflowBuilder
{
    public function __construct(
        private WorkflowRepository $workflowRepository,
        private WorkflowStepRepository $stepRepository
    ) {}

    public function create(array $data): Workflow
    {
        return DB::transaction(function () use ($data) {
            $workflowData = [
                'tenant_id' => $data['tenant_id'] ?? auth()->user()->tenant_id,
                'organization_id' => $data['organization_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'code' => $data['code'] ?? $this->generateCode($data['name']),
                'status' => WorkflowStatus::DRAFT,
                'trigger_type' => $data['trigger_type'] ?? 'manual',
                'trigger_config' => $data['trigger_config'] ?? [],
                'entity_type' => $data['entity_type'] ?? null,
                'entity_id' => $data['entity_id'] ?? null,
                'version' => $data['version'] ?? 1,
                'is_template' => $data['is_template'] ?? false,
                'metadata' => $data['metadata'] ?? [],
                'created_by' => auth()->id(),
            ];

            $workflow = $this->workflowRepository->create($workflowData);

            if (isset($data['steps']) && is_array($data['steps'])) {
                $this->addSteps($workflow, $data['steps']);
            }

            event(new WorkflowCreated($workflow));

            return $workflow->fresh(['steps.conditions']);
        });
    }

    public function update(Workflow $workflow, array $data): Workflow
    {
        if (! $workflow->canEdit()) {
            throw new WorkflowBuilderException('Cannot edit workflow in current status');
        }

        return DB::transaction(function () use ($workflow, $data) {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'trigger_type' => $data['trigger_type'] ?? null,
                'trigger_config' => $data['trigger_config'] ?? null,
                'entity_type' => $data['entity_type'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'updated_by' => auth()->id(),
            ], fn ($value) => $value !== null);

            $workflow = $this->workflowRepository->updateWorkflow($workflow, $updateData);

            if (isset($data['steps'])) {
                $workflow->steps()->delete();
                $this->addSteps($workflow, $data['steps']);
            }

            event(new WorkflowUpdated($workflow));

            return $workflow->fresh(['steps.conditions']);
        });
    }

    public function addStep(Workflow $workflow, array $stepData): WorkflowStep
    {
        if (! $workflow->canEdit()) {
            throw new WorkflowBuilderException('Cannot modify workflow in current status');
        }

        $sequence = $stepData['sequence'] ?? $workflow->steps()->max('sequence') + 1;

        $step = $this->stepRepository->create([
            'workflow_id' => $workflow->id,
            'name' => $stepData['name'],
            'description' => $stepData['description'] ?? null,
            'type' => StepType::from($stepData['type']),
            'sequence' => $sequence,
            'config' => $stepData['config'] ?? [],
            'action_config' => $stepData['action_config'] ?? null,
            'approval_config' => $stepData['approval_config'] ?? null,
            'condition_config' => $stepData['condition_config'] ?? null,
            'timeout_seconds' => $stepData['timeout_seconds'] ?? null,
            'retry_count' => $stepData['retry_count'] ?? 0,
            'is_required' => $stepData['is_required'] ?? true,
            'metadata' => $stepData['metadata'] ?? [],
        ]);

        if (isset($stepData['conditions']) && is_array($stepData['conditions'])) {
            $this->addConditions($step, $stepData['conditions']);
        }

        return $step;
    }

    public function addSteps(Workflow $workflow, array $steps): void
    {
        foreach ($steps as $stepData) {
            $this->addStep($workflow, $stepData);
        }
    }

    public function addConditions(WorkflowStep $step, array $conditions): void
    {
        foreach ($conditions as $sequence => $conditionData) {
            $step->conditions()->create([
                'type' => $conditionData['type'],
                'field' => $conditionData['field'],
                'operator' => $conditionData['operator'] ?? 'equals',
                'value' => $conditionData['value'],
                'next_step_id' => $conditionData['next_step_id'] ?? null,
                'is_default' => $conditionData['is_default'] ?? false,
                'sequence' => $sequence,
                'metadata' => $conditionData['metadata'] ?? [],
            ]);
        }
    }

    public function validate(Workflow $workflow): array
    {
        $errors = [];

        $startSteps = $workflow->steps()->where('type', StepType::START)->count();
        if ($startSteps === 0) {
            $errors[] = 'Workflow must have at least one START step';
        } elseif ($startSteps > 1) {
            $errors[] = 'Workflow can only have one START step';
        }

        $endSteps = $workflow->steps()->where('type', StepType::END)->count();
        if ($endSteps === 0) {
            $errors[] = 'Workflow must have at least one END step';
        }

        foreach ($workflow->steps as $step) {
            if ($step->type === StepType::ACTION && empty($step->action_config)) {
                $errors[] = "Step '{$step->name}' (ACTION) requires action_config";
            }

            if ($step->type === StepType::APPROVAL && empty($step->approval_config)) {
                $errors[] = "Step '{$step->name}' (APPROVAL) requires approval_config";
            }

            if ($step->type === StepType::CONDITION && $step->conditions()->count() === 0) {
                $errors[] = "Step '{$step->name}' (CONDITION) requires at least one condition";
            }
        }

        return $errors;
    }

    public function activate(Workflow $workflow): void
    {
        $errors = $this->validate($workflow);
        if (! empty($errors)) {
            throw new WorkflowBuilderException('Workflow validation failed: '.implode(', ', $errors));
        }

        $workflow->activate();
    }

    private function generateCode(string $name): string
    {
        return strtoupper(str_replace(' ', '_', $name)).'_'.time();
    }
}
