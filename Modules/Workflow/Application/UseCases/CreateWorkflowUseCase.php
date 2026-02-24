<?php

namespace Modules\Workflow\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryInterface;
use Modules\Workflow\Domain\Events\WorkflowCreated;

class CreateWorkflowUseCase
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['states']) || ! is_array($data['states'])) {
                throw new DomainException('Workflow must define at least one state.');
            }

            $workflow = $this->workflowRepo->create([
                'tenant_id'     => $data['tenant_id'],
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'document_type' => $data['document_type'],
                'states'        => $data['states'],
                'transitions'   => $data['transitions'] ?? [],
                'is_active'     => $data['is_active'] ?? true,
            ]);

            Event::dispatch(new WorkflowCreated(
                $workflow->id,
                $workflow->tenant_id,
                $workflow->name,
                $workflow->document_type,
            ));

            return $workflow;
        });
    }
}
