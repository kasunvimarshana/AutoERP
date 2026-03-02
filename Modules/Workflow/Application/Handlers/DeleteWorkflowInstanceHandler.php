<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Workflow\Application\Commands\DeleteWorkflowInstanceCommand;
use Modules\Workflow\Domain\Contracts\WorkflowInstanceRepositoryInterface;

class DeleteWorkflowInstanceHandler extends BaseHandler
{
    public function __construct(
        private readonly WorkflowInstanceRepositoryInterface $instanceRepository,
    ) {}

    public function handle(DeleteWorkflowInstanceCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->instanceRepository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException(
                    "Workflow instance with ID '{$command->id}' not found."
                );
            }

            $this->instanceRepository->delete($command->id, $command->tenantId);
        });
    }
}
