<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Wms\Application\Commands\DeleteAisleCommand;
use Modules\Wms\Domain\Contracts\AisleRepositoryInterface;

class DeleteAisleHandler extends BaseHandler
{
    public function __construct(
        private readonly AisleRepositoryInterface $repository,
    ) {}

    public function handle(DeleteAisleCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Aisle with ID '{$command->id}' not found.");
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
