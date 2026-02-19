<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Modules\Workflow\Enums\ApprovalStatus;
use Modules\Workflow\Events\ApprovalCreated;
use Modules\Workflow\Events\ApprovalResponded;
use Modules\Workflow\Exceptions\ApprovalException;
use Modules\Workflow\Models\Approval;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Models\WorkflowStep;
use Modules\Workflow\Repositories\ApprovalRepository;

class ApprovalService
{
    public function __construct(
        private ApprovalRepository $approvalRepository,
        private WorkflowEngine $engine
    ) {}

    public function createApproval(WorkflowInstance $instance, WorkflowStep $step): Approval
    {
        $config = $step->approval_config ?? [];

        $approval = DB::transaction(function () use ($instance, $step, $config) {
            $approval = $this->approvalRepository->create([
                'tenant_id' => $instance->tenant_id,
                'organization_id' => $instance->organization_id,
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step->id,
                'approver_id' => $config['approver_id'] ?? null,
                'status' => ApprovalStatus::PENDING,
                'priority' => $config['priority'] ?? 1,
                'subject' => $config['subject'] ?? "Approval Required: {$instance->workflow->name}",
                'description' => $config['description'] ?? null,
                'due_at' => isset($config['due_hours']) ? now()->addHours($config['due_hours']) : null,
                'requested_at' => now(),
            ]);

            event(new ApprovalCreated($approval));

            return $approval;
        });

        return $approval;
    }

    public function approve(Approval $approval, array $data = [], ?int $userId = null): void
    {
        if ($approval->isFinal()) {
            throw new ApprovalException('Approval already finalized');
        }

        $this->validateApprover($approval, $userId);

        DB::transaction(function () use ($approval, $data) {
            $approval->approve($data);

            event(new ApprovalResponded($approval, true));

            $instance = $approval->instance;
            $this->engine->resume($instance, ['approval_result' => 'approved']);
        });
    }

    public function reject(Approval $approval, array $data = [], ?int $userId = null): void
    {
        if ($approval->isFinal()) {
            throw new ApprovalException('Approval already finalized');
        }

        $this->validateApprover($approval, $userId);

        DB::transaction(function () use ($approval, $data) {
            $approval->reject($data);

            event(new ApprovalResponded($approval, false));

            $instance = $approval->instance;

            $rejectConfig = $approval->step->approval_config['on_reject'] ?? [];
            if (isset($rejectConfig['action']) && $rejectConfig['action'] === 'fail') {
                $instance->fail('Approval rejected');
            } else {
                $this->engine->resume($instance, ['approval_result' => 'rejected']);
            }
        });
    }

    public function delegate(Approval $approval, int $delegateToUserId, ?int $userId = null): void
    {
        if ($approval->isFinal()) {
            throw new ApprovalException('Approval already finalized');
        }

        $this->validateApprover($approval, $userId);

        $approval->delegate($delegateToUserId);
    }

    public function escalate(Approval $approval): void
    {
        $config = $approval->step->approval_config ?? [];
        $escalationChain = $config['escalation_chain'] ?? [];

        $currentLevel = $approval->escalation_level ?? 0;
        $nextLevel = $currentLevel + 1;

        if (! isset($escalationChain[$nextLevel])) {
            return;
        }

        $approval->update([
            'approver_id' => $escalationChain[$nextLevel],
            'escalation_level' => $nextLevel,
            'escalated_at' => now(),
        ]);
    }

    public function getPendingApprovals(int $userId): array
    {
        return $this->approvalRepository->getPendingForUser($userId)->toArray();
    }

    public function processOverdueApprovals(): void
    {
        $overdueApprovals = $this->approvalRepository->getOverdue();

        foreach ($overdueApprovals as $approval) {
            $this->escalate($approval);
        }
    }

    private function validateApprover(Approval $approval, ?int $userId): void
    {
        $userId = $userId ?? auth()->id();

        $validApprovers = array_filter([
            $approval->approver_id,
            $approval->delegated_to,
        ]);

        if (! in_array($userId, $validApprovers)) {
            throw new ApprovalException('User not authorized to respond to this approval');
        }
    }
}
