<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Wms\Application\Commands\DeleteZoneCommand;
use Modules\Wms\Domain\Contracts\ZoneRepositoryInterface;

class DeleteZoneHandler extends BaseHandler
{
    public function __construct(
        private readonly ZoneRepositoryInterface $repository,
    ) {}

    public function handle(DeleteZoneCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Zone with ID '{$command->id}' not found.");
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
