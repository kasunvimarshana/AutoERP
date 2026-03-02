<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Pos\Application\Commands\DeletePosSessionCommand;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;

class DeletePosSessionHandler extends BaseHandler
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $repository,
    ) {}

    public function handle(DeletePosSessionCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);
            if ($existing === null) {
                throw new \DomainException("POS session with ID '{$command->id}' not found.");
            }
            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
