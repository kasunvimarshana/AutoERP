<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\UpdateBinCommand;
use Modules\Wms\Domain\Contracts\BinRepositoryInterface;
use Modules\Wms\Domain\Entities\Bin;

class UpdateBinHandler extends BaseHandler
{
    public function __construct(
        private readonly BinRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateBinCommand $command): Bin
    {
        return $this->transaction(function () use ($command): Bin {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateBinCommand $cmd): Bin {
                    $existing = $this->repository->findById($cmd->id, $cmd->tenantId);

                    if ($existing === null) {
                        throw new \DomainException("Bin with ID '{$cmd->id}' not found.");
                    }

                    return $this->repository->save(new Bin(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        aisleId: $existing->aisleId,
                        code: $existing->code,
                        description: $cmd->description,
                        maxCapacity: $cmd->maxCapacity,
                        currentCapacity: $existing->currentCapacity,
                        isActive: $cmd->isActive,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
