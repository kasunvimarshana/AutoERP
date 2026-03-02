<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Workflow\Application\Commands\CreateWorkflowDefinitionCommand;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowDefinition;
use Modules\Workflow\Domain\Enums\WorkflowDefinitionStatus;

class CreateWorkflowDefinitionHandler extends BaseHandler
{
    public function __construct(
        private readonly WorkflowDefinitionRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateWorkflowDefinitionCommand $command): WorkflowDefinition
    {
        return $this->transaction(function () use ($command): WorkflowDefinition {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateWorkflowDefinitionCommand $cmd): WorkflowDefinition {
                    $existing = $this->repository->findAll($cmd->tenantId, 1, 1000);
                    foreach ($existing['items'] as $def) {
                        if ($def->name === $cmd->name) {
                            throw new \DomainException(
                                "A workflow definition named '{$cmd->name}' already exists for this tenant."
                            );
                        }
                    }

                    $definition = new WorkflowDefinition(
                        id: null,
                        tenantId: $cmd->tenantId,
                        name: $cmd->name,
                        description: $cmd->description,
                        entityType: $cmd->entityType,
                        status: WorkflowDefinitionStatus::Active->value,
                        isActive: true,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->repository->save($definition, $cmd->states, $cmd->transitions);
                });
        });
    }
}
