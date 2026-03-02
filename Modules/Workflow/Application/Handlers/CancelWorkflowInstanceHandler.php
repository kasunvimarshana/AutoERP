<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Workflow\Application\Commands\CancelWorkflowInstanceCommand;
use Modules\Workflow\Domain\Contracts\WorkflowInstanceRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowInstance;
use Modules\Workflow\Domain\Entities\WorkflowInstanceLog;
use Modules\Workflow\Domain\Enums\WorkflowInstanceStatus;

class CancelWorkflowInstanceHandler extends BaseHandler
{
    public function __construct(
        private readonly WorkflowInstanceRepositoryInterface $instanceRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CancelWorkflowInstanceCommand $command): WorkflowInstance
    {
        return $this->transaction(function () use ($command): WorkflowInstance {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CancelWorkflowInstanceCommand $cmd): WorkflowInstance {
                    $instance = $this->instanceRepository->findById($cmd->instanceId, $cmd->tenantId);

                    if ($instance === null) {
                        throw new \DomainException(
                            "Workflow instance with ID '{$cmd->instanceId}' not found."
                        );
                    }

                    if ($instance->status !== WorkflowInstanceStatus::Active->value) {
                        throw new \DomainException(
                            "Workflow instance '{$cmd->instanceId}' cannot be cancelled (status: {$instance->status})."
                        );
                    }

                    $now = now()->toIso8601String();

                    $updated = new WorkflowInstance(
                        id: $instance->id,
                        tenantId: $instance->tenantId,
                        workflowDefinitionId: $instance->workflowDefinitionId,
                        entityType: $instance->entityType,
                        entityId: $instance->entityId,
                        currentStateId: $instance->currentStateId,
                        status: WorkflowInstanceStatus::Cancelled->value,
                        startedAt: $instance->startedAt,
                        completedAt: null,
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
                        toStateId: $instance->currentStateId,
                        transitionId: null,
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
