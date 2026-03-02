<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\UpdateAisleCommand;
use Modules\Wms\Domain\Contracts\AisleRepositoryInterface;
use Modules\Wms\Domain\Entities\Aisle;

class UpdateAisleHandler extends BaseHandler
{
    public function __construct(
        private readonly AisleRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateAisleCommand $command): Aisle
    {
        return $this->transaction(function () use ($command): Aisle {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateAisleCommand $cmd): Aisle {
                    $existing = $this->repository->findById($cmd->id, $cmd->tenantId);

                    if ($existing === null) {
                        throw new \DomainException("Aisle with ID '{$cmd->id}' not found.");
                    }

                    return $this->repository->save(new Aisle(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        zoneId: $existing->zoneId,
                        name: $cmd->name,
                        code: $existing->code,
                        description: $cmd->description,
                        sortOrder: $cmd->sortOrder,
                        isActive: $cmd->isActive,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
