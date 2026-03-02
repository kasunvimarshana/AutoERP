<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Workflow\Application\Commands\DeleteWorkflowDefinitionCommand;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;

class DeleteWorkflowDefinitionHandler extends BaseHandler
{
    public function __construct(
        private readonly WorkflowDefinitionRepositoryInterface $repository,
    ) {}

    public function handle(DeleteWorkflowDefinitionCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException(
                    "Workflow definition with ID '{$command->id}' not found."
                );
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
