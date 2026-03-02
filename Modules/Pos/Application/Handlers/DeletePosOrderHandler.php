<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Pos\Application\Commands\DeletePosOrderCommand;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;

class DeletePosOrderHandler extends BaseHandler
{
    public function __construct(
        private readonly PosOrderRepositoryInterface $repository,
    ) {}

    public function handle(DeletePosOrderCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);
            if ($existing === null) {
                throw new \DomainException("POS order with ID '{$command->id}' not found.");
            }
            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
