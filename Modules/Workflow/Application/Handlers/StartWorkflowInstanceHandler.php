<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Workflow\Application\Commands\StartWorkflowInstanceCommand;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;
use Modules\Workflow\Domain\Contracts\WorkflowInstanceRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowInstance;
use Modules\Workflow\Domain\Entities\WorkflowInstanceLog;
use Modules\Workflow\Domain\Enums\WorkflowInstanceStatus;

class StartWorkflowInstanceHandler extends BaseHandler
{
    public function __construct(
        private readonly WorkflowDefinitionRepositoryInterface $definitionRepository,
        private readonly WorkflowInstanceRepositoryInterface $instanceRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(StartWorkflowInstanceCommand $command): WorkflowInstance
    {
        return $this->transaction(function () use ($command): WorkflowInstance {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (StartWorkflowInstanceCommand $cmd): WorkflowInstance {
                    $definition = $this->definitionRepository->findById(
                        $cmd->workflowDefinitionId,
                        $cmd->tenantId
                    );

                    if ($definition === null) {
                        throw new \DomainException(
                            "Workflow definition with ID '{$cmd->workflowDefinitionId}' not found."
                        );
                    }

                    if (! $definition->isActive) {
                        throw new \DomainException(
                            "Workflow definition '{$definition->name}' is not active."
                        );
                    }

                    $states = $this->definitionRepository->findStates(
                        $cmd->workflowDefinitionId,
                        $cmd->tenantId
                    );

                    $initialState = null;
                    foreach ($states as $state) {
                        if ($state->isInitial) {
                            $initialState = $state;
                            break;
                        }
                    }

                    if ($initialState === null) {
                        throw new \DomainException(
                            "No initial state found for workflow definition '{$definition->name}'."
                        );
                    }

                    $now = now()->toIso8601String();

                    $instance = new WorkflowInstance(
                        id: null,
                        tenantId: $cmd->tenantId,
                        workflowDefinitionId: $cmd->workflowDefinitionId,
                        entityType: $cmd->entityType,
                        entityId: $cmd->entityId,
                        currentStateId: $initialState->id,
                        status: WorkflowInstanceStatus::Active->value,
                        startedAt: $now,
                        completedAt: null,
                        startedByUserId: $cmd->startedByUserId,
                        createdAt: null,
                        updatedAt: null,
                    );

                    $saved = $this->instanceRepository->save($instance);

                    $log = new WorkflowInstanceLog(
                        id: null,
                        workflowInstanceId: $saved->id,
                        tenantId: $cmd->tenantId,
                        fromStateId: null,
                        toStateId: $initialState->id,
                        transitionId: null,
                        comment: null,
                        actorUserId: $cmd->startedByUserId ?? 0,
                        actedAt: $now,
                        createdAt: null,
                    );

                    $this->instanceRepository->saveLog($log);

                    return $saved;
                });
        });
    }
}
