<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Services;

use Modules\Workflow\Application\Commands\CreateWorkflowDefinitionCommand;
use Modules\Workflow\Application\Commands\DeleteWorkflowDefinitionCommand;
use Modules\Workflow\Application\Commands\UpdateWorkflowDefinitionCommand;
use Modules\Workflow\Application\Handlers\CreateWorkflowDefinitionHandler;
use Modules\Workflow\Application\Handlers\DeleteWorkflowDefinitionHandler;
use Modules\Workflow\Application\Handlers\UpdateWorkflowDefinitionHandler;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowDefinition;

class WorkflowDefinitionService
{
    public function __construct(
        private readonly WorkflowDefinitionRepositoryInterface $repository,
        private readonly CreateWorkflowDefinitionHandler $createHandler,
        private readonly UpdateWorkflowDefinitionHandler $updateHandler,
        private readonly DeleteWorkflowDefinitionHandler $deleteHandler,
    ) {}

    public function createDefinition(CreateWorkflowDefinitionCommand $cmd): WorkflowDefinition
    {
        return $this->createHandler->handle($cmd);
    }

    public function updateDefinition(UpdateWorkflowDefinitionCommand $cmd): WorkflowDefinition
    {
        return $this->updateHandler->handle($cmd);
    }

    public function deleteDefinition(DeleteWorkflowDefinitionCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findDefinitionById(int $id, int $tenantId): ?WorkflowDefinition
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAllDefinitions(int $tenantId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $page, $perPage);
    }

    public function findDefinitionStates(int $definitionId, int $tenantId): array
    {
        return $this->repository->findStates($definitionId, $tenantId);
    }

    public function findDefinitionTransitions(int $definitionId, int $tenantId): array
    {
        return $this->repository->findTransitions($definitionId, $tenantId);
    }
}
