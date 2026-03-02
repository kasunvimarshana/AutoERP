<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\CreateZoneCommand;
use Modules\Wms\Domain\Contracts\ZoneRepositoryInterface;
use Modules\Wms\Domain\Entities\Zone;

class CreateZoneHandler extends BaseHandler
{
    public function __construct(
        private readonly ZoneRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateZoneCommand $command): Zone
    {
        return $this->transaction(function () use ($command): Zone {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateZoneCommand $cmd): Zone {
                    $existing = $this->repository->findByWarehouse($cmd->tenantId, $cmd->warehouseId);
                    foreach ($existing as $zone) {
                        if (strtolower($zone->code) === strtolower($cmd->code)) {
                            throw new \DomainException(
                                "A zone with code '{$cmd->code}' already exists in this warehouse."
                            );
                        }
                    }

                    return $this->repository->save(new Zone(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->warehouseId,
                        name: $cmd->name,
                        code: $cmd->code,
                        description: $cmd->description,
                        sortOrder: $cmd->sortOrder,
                        isActive: true,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
