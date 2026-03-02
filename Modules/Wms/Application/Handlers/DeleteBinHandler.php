<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Wms\Application\Commands\DeleteBinCommand;
use Modules\Wms\Domain\Contracts\BinRepositoryInterface;

class DeleteBinHandler extends BaseHandler
{
    public function __construct(
        private readonly BinRepositoryInterface $repository,
    ) {}

    public function handle(DeleteBinCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $existing = $this->repository->findById($command->id, $command->tenantId);

            if ($existing === null) {
                throw new \DomainException("Bin with ID '{$command->id}' not found.");
            }

            $this->repository->delete($command->id, $command->tenantId);
        });
    }
}
