<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Workflow\Application\Commands\UpdateWorkflowDefinitionCommand;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowDefinition;

class UpdateWorkflowDefinitionHandler extends BaseHandler
{
    public function __construct(
        private readonly WorkflowDefinitionRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateWorkflowDefinitionCommand $command): WorkflowDefinition
    {
        return $this->transaction(function () use ($command): WorkflowDefinition {
            $existing = $this->repository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException(
                    "Workflow definition with ID '{$command->id}' not found."
                );
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateWorkflowDefinitionCommand $cmd) use ($existing): WorkflowDefinition {
                    $updated = new WorkflowDefinition(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        name: $cmd->name ?? $existing->name,
                        description: $cmd->description ?? $existing->description,
                        entityType: $existing->entityType,
                        status: $existing->status,
                        isActive: $cmd->isActive ?? $existing->isActive,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    );

                    return $this->repository->save($updated);
                });
        });
    }
}
