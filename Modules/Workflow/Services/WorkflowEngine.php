<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Workflow\Enums\InstanceStatus;
use Modules\Workflow\Enums\StepType;
use Modules\Workflow\Events\WorkflowInstanceCompleted;
use Modules\Workflow\Events\WorkflowInstanceFailed;
use Modules\Workflow\Events\WorkflowStepCompleted;
use Modules\Workflow\Events\WorkflowStepStarted;
use Modules\Workflow\Exceptions\WorkflowExecutionException;
use Modules\Workflow\Models\Workflow;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Models\WorkflowInstanceStep;
use Modules\Workflow\Models\WorkflowStep;
use Modules\Workflow\Repositories\WorkflowInstanceRepository;

class WorkflowEngine
{
    public function __construct(
        private WorkflowInstanceRepository $instanceRepository,
        private WorkflowExecutor $executor,
        private ApprovalService $approvalService
    ) {}

    public function start(Workflow $workflow, array $context = [], ?int $userId = null): WorkflowInstance
    {
        if (! $workflow->canExecute()) {
            throw new WorkflowExecutionException("Workflow {$workflow->name} cannot be executed");
        }

        return DB::transaction(function () use ($workflow, $context, $userId) {
            $instance = $this->instanceRepository->create([
                'workflow_id' => $workflow->id,
                'tenant_id' => $workflow->tenant_id,
                'organization_id' => $workflow->organization_id,
                'status' => InstanceStatus::RUNNING,
                'context' => $context,
                'entity_type' => $context['entity_type'] ?? null,
                'entity_id' => $context['entity_id'] ?? null,
                'started_by' => $userId ?? auth()->id(),
                'started_at' => now(),
            ]);

            $startStep = $workflow->steps()->where('type', StepType::START)->first();
            if (! $startStep) {
                throw new WorkflowExecutionException('Workflow has no start step');
            }

            $this->executeStep($instance, $startStep);

            return $instance;
        });
    }

    public function resume(WorkflowInstance $instance, array $data = []): void
    {
        if ($instance->isFinal()) {
            throw new WorkflowExecutionException('Workflow instance is already finalized');
        }

        DB::transaction(function () use ($instance, $data) {
            $instance->update([
                'status' => InstanceStatus::RUNNING,
                'context' => array_merge($instance->context ?? [], $data),
            ]);

            if ($instance->current_step_id) {
                $currentStep = $instance->currentStep;
                $this->executeStep($instance, $currentStep);
            }
        });
    }

    public function executeStep(WorkflowInstance $instance, WorkflowStep $step): void
    {
        try {
            $instanceStep = $this->createInstanceStep($instance, $step);
            $instanceStep->start();

            event(new WorkflowStepStarted($instance, $step, $instanceStep));

            $result = match ($step->type) {
                StepType::START => $this->executeStartStep($instance, $step),
                StepType::ACTION => $this->executeActionStep($instance, $step),
                StepType::APPROVAL => $this->executeApprovalStep($instance, $step),
                StepType::CONDITION => $this->executeConditionStep($instance, $step),
                StepType::PARALLEL => $this->executeParallelStep($instance, $step),
                StepType::END => $this->executeEndStep($instance, $step),
            };

            $instanceStep->complete($result);
            event(new WorkflowStepCompleted($instance, $step, $instanceStep, $result));

            if ($step->type !== StepType::END && $step->type !== StepType::APPROVAL) {
                $this->transitionToNext($instance, $step, $result);
            }
        } catch (\Exception $e) {
            Log::error('Workflow step execution failed', [
                'instance_id' => $instance->id,
                'step_id' => $step->id,
                'error' => $e->getMessage(),
            ]);

            $instanceStep->fail($e->getMessage());
            $instance->fail($e->getMessage());

            event(new WorkflowInstanceFailed($instance, $e->getMessage()));
            throw new WorkflowExecutionException("Step execution failed: {$e->getMessage()}", 0, $e);
        }
    }

    private function executeStartStep(WorkflowInstance $instance, WorkflowStep $step): array
    {
        return ['started' => true];
    }

    private function executeActionStep(WorkflowInstance $instance, WorkflowStep $step): array
    {
        return $this->executor->executeAction($instance, $step);
    }

    private function executeApprovalStep(WorkflowInstance $instance, WorkflowStep $step): array
    {
        $approval = $this->approvalService->createApproval($instance, $step);

        $instance->update([
            'status' => InstanceStatus::WAITING,
            'current_step_id' => $step->id,
        ]);

        return ['approval_id' => $approval->id, 'waiting' => true];
    }

    private function executeConditionStep(WorkflowInstance $instance, WorkflowStep $step): array
    {
        return $this->executor->evaluateConditions($instance, $step);
    }

    private function executeParallelStep(WorkflowInstance $instance, WorkflowStep $step): array
    {
        return $this->executor->executeParallel($instance, $step);
    }

    private function executeEndStep(WorkflowInstance $instance, WorkflowStep $step): array
    {
        $instance->complete();
        event(new WorkflowInstanceCompleted($instance));

        return ['completed' => true];
    }

    private function transitionToNext(WorkflowInstance $instance, WorkflowStep $step, array $result): void
    {
        $nextSteps = $this->determineNextSteps($instance, $step, $result);

        if (empty($nextSteps)) {
            $instance->complete();
            event(new WorkflowInstanceCompleted($instance));

            return;
        }

        foreach ($nextSteps as $nextStep) {
            $this->executeStep($instance, $nextStep);
        }
    }

    private function determineNextSteps(WorkflowInstance $instance, WorkflowStep $step, array $result): array
    {
        if ($step->type === StepType::CONDITION) {
            return $result['next_steps'] ?? [];
        }

        $nextStepIds = $step->getNextSteps();
        if (empty($nextStepIds)) {
            return [];
        }

        return WorkflowStep::whereIn('id', $nextStepIds)
            ->where('workflow_id', $instance->workflow_id)
            ->orderBy('sequence')
            ->get()
            ->all();
    }

    private function createInstanceStep(WorkflowInstance $instance, WorkflowStep $step): WorkflowInstanceStep
    {
        return WorkflowInstanceStep::create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step->id,
            'status' => InstanceStatus::PENDING,
            'input_data' => $instance->context,
        ]);
    }
}
