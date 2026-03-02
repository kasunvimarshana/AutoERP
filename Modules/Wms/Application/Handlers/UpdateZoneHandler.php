<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\UpdateZoneCommand;
use Modules\Wms\Domain\Contracts\ZoneRepositoryInterface;
use Modules\Wms\Domain\Entities\Zone;

class UpdateZoneHandler extends BaseHandler
{
    public function __construct(
        private readonly ZoneRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateZoneCommand $command): Zone
    {
        return $this->transaction(function () use ($command): Zone {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateZoneCommand $cmd): Zone {
                    $existing = $this->repository->findById($cmd->id, $cmd->tenantId);

                    if ($existing === null) {
                        throw new \DomainException("Zone with ID '{$cmd->id}' not found.");
                    }

                    return $this->repository->save(new Zone(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        warehouseId: $existing->warehouseId,
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
