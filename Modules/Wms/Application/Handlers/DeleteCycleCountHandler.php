<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Wms\Application\Commands\DeleteCycleCountCommand;
use Modules\Wms\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Wms\Domain\Enums\CycleCountStatus;

class DeleteCycleCountHandler extends BaseHandler
{
    public function __construct(
        private readonly CycleCountRepositoryInterface $repository,
    ) {}

    public function handle(DeleteCycleCountCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Cycle count with ID '{$command->id}' not found.");
            }

            if ($existing->status !== CycleCountStatus::Draft->value) {
                throw new \DomainException('Only draft cycle counts can be deleted.');
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
