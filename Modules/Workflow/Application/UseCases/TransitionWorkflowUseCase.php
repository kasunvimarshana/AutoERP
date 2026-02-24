<?php

namespace Modules\Workflow\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Workflow\Domain\Contracts\WorkflowHistoryRepositoryInterface;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryInterface;
use Modules\Workflow\Domain\Events\WorkflowTransitioned;

class TransitionWorkflowUseCase
{
    public function __construct(
        private WorkflowRepositoryInterface        $workflowRepo,
        private WorkflowHistoryRepositoryInterface $historyRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $workflow = $this->workflowRepo->findById($data['workflow_id']);

            if (! $workflow) {
                throw new DomainException('Workflow not found.');
            }

            $transitions = $workflow->transitions ?? [];
            $allowed     = false;
            foreach ($transitions as $transition) {
                if ($transition['from'] === $data['from_state'] && $transition['to'] === $data['to_state']) {
                    $allowed = true;
                    break;
                }
            }

            if (! $allowed) {
                throw new DomainException(
                    "Transition from '{$data['from_state']}' to '{$data['to_state']}' is not permitted."
                );
            }

            $history = $this->historyRepo->create([
                'tenant_id'     => $data['tenant_id'],
                'workflow_id'   => $workflow->id,
                'document_type' => $data['document_type'],
                'document_id'   => $data['document_id'],
                'from_state'    => $data['from_state'],
                'to_state'      => $data['to_state'],
                'actor_id'      => $data['actor_id'],
                'comment'       => $data['comment'] ?? null,
            ]);

            Event::dispatch(new WorkflowTransitioned(
                $workflow->id,
                $data['tenant_id'],
                $data['document_type'],
                $data['document_id'],
                $data['from_state'],
                $data['to_state'],
                $data['actor_id'],
            ));

            return $history;
        });
    }
}
