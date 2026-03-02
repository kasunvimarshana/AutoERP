<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Services;

use Modules\Workflow\Application\Commands\AdvanceWorkflowInstanceCommand;
use Modules\Workflow\Application\Commands\CancelWorkflowInstanceCommand;
use Modules\Workflow\Application\Commands\DeleteWorkflowInstanceCommand;
use Modules\Workflow\Application\Commands\StartWorkflowInstanceCommand;
use Modules\Workflow\Application\Handlers\AdvanceWorkflowInstanceHandler;
use Modules\Workflow\Application\Handlers\CancelWorkflowInstanceHandler;
use Modules\Workflow\Application\Handlers\DeleteWorkflowInstanceHandler;
use Modules\Workflow\Application\Handlers\StartWorkflowInstanceHandler;
use Modules\Workflow\Domain\Contracts\WorkflowInstanceRepositoryInterface;
use Modules\Workflow\Domain\Entities\WorkflowInstance;

class WorkflowInstanceService
{
    public function __construct(
        private readonly WorkflowInstanceRepositoryInterface $repository,
        private readonly StartWorkflowInstanceHandler $startHandler,
        private readonly AdvanceWorkflowInstanceHandler $advanceHandler,
        private readonly CancelWorkflowInstanceHandler $cancelHandler,
        private readonly DeleteWorkflowInstanceHandler $deleteHandler,
    ) {}

    public function startInstance(StartWorkflowInstanceCommand $cmd): WorkflowInstance
    {
        return $this->startHandler->handle($cmd);
    }

    public function advanceInstance(AdvanceWorkflowInstanceCommand $cmd): WorkflowInstance
    {
        return $this->advanceHandler->handle($cmd);
    }

    public function cancelInstance(CancelWorkflowInstanceCommand $cmd): WorkflowInstance
    {
        return $this->cancelHandler->handle($cmd);
    }

    public function deleteInstance(DeleteWorkflowInstanceCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findInstanceById(int $id, int $tenantId): ?WorkflowInstance
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAllInstances(int $tenantId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $page, $perPage);
    }

    public function findInstancesByEntity(string $entityType, int $entityId, int $tenantId): array
    {
        return $this->repository->findByEntity($entityType, $entityId, $tenantId);
    }

    public function findInstanceLogs(int $instanceId, int $tenantId): array
    {
        return $this->repository->findLogs($instanceId, $tenantId);
    }
}
