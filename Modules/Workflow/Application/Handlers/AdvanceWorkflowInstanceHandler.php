<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Workflow\Application\Commands\AdvanceWorkflowInstanceCommand;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;
use Modules\Workflow\Domain\Contracts\WorkflowInstanceRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowInstance;
use Modules\Workflow\Domain\Entities\WorkflowInstanceLog;
use Modules\Workflow\Domain\Enums\WorkflowInstanceStatus;

class AdvanceWorkflowInstanceHandler extends BaseHandler
{
    public function __construct(
        private readonly WorkflowDefinitionRepositoryInterface $definitionRepository,
        private readonly WorkflowInstanceRepositoryInterface $instanceRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(AdvanceWorkflowInstanceCommand $command): WorkflowInstance
    {
        return $this->transaction(function () use ($command): WorkflowInstance {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (AdvanceWorkflowInstanceCommand $cmd): WorkflowInstance {
                    $instance = $this->instanceRepository->findById($cmd->instanceId, $cmd->tenantId);

                    if ($instance === null) {
                        throw new \DomainException(
                            "Workflow instance with ID '{$cmd->instanceId}' not found."
                        );
                    }

                    if ($instance->status !== WorkflowInstanceStatus::Active->value) {
                        throw new \DomainException(
                            "Workflow instance '{$cmd->instanceId}' is not active (status: {$instance->status})."
                        );
                    }

                    $transitions = $this->definitionRepository->findTransitions(
                        $instance->workflowDefinitionId,
                        $cmd->tenantId
                    );

                    $transition = null;
                    foreach ($transitions as $t) {
                        if ($t->id === $cmd->transitionId) {
                            $transition = $t;
                            break;
                        }
                    }

                    if ($transition === null) {
                        throw new \DomainException(
                            "Transition with ID '{$cmd->transitionId}' not found for this workflow."
                        );
                    }

                    if ($transition->fromStateId !== $instance->currentStateId) {
                        throw new \DomainException(
                            "Transition '{$transition->name}' cannot be applied: instance is not in the expected from-state."
                        );
                    }

                    if ($transition->requiresComment && empty($cmd->comment)) {
                        throw new \DomainException(
                            "Transition '{$transition->name}' requires a comment."
                        );
                    }

                    $states = $this->definitionRepository->findStates(
                        $instance->workflowDefinitionId,
                        $cmd->tenantId
                    );

                    $targetState = null;
                    foreach ($states as $s) {
                        if ($s->id === $transition->toStateId) {
                            $targetState = $s;
                            break;
                        }
                    }

                    if ($targetState === null) {
                        throw new \DomainException('Target state not found.');
                    }

                    $now = now()->toIso8601String();
                    $isCompleted = $targetState->isFinal;

                    $updated = new WorkflowInstance(
                        id: $instance->id,
                        tenantId: $instance->tenantId,
                        workflowDefinitionId: $instance->workflowDefinitionId,
                        entityType: $instance->entityType,
                        entityId: $instance->entityId,
                        currentStateId: $transition->toStateId,
                        status: $isCompleted
                            ? WorkflowInstanceStatus::Completed->value
                            : WorkflowInstanceStatus::Active->value,
                        startedAt: $instance->startedAt,
                        completedAt: $isCompleted ? $now : null,
                        startedByUserId: $instance->startedByUserId,
                        createdAt: $instance->createdAt,
                        updatedAt: null,
                    );

                    $saved = $this->instanceRepository->save($updated);

                    $log = new WorkflowInstanceLog(
                        id: null,
                        workflowInstanceId: $saved->id,
                        tenantId: $cmd->tenantId,
                        fromStateId: $instance->currentStateId,
                        toStateId: $transition->toStateId,
                        transitionId: $cmd->transitionId,
                        comment: $cmd->comment,
                        actorUserId: $cmd->actorUserId,
                        actedAt: $now,
                        createdAt: null,
                    );

                    $this->instanceRepository->saveLog($log);

                    return $saved;
                });
        });
    }
}
