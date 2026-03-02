<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\CreateAisleCommand;
use Modules\Wms\Domain\Contracts\AisleRepositoryInterface;
use Modules\Wms\Domain\Entities\Aisle;

class CreateAisleHandler extends BaseHandler
{
    public function __construct(
        private readonly AisleRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateAisleCommand $command): Aisle
    {
        return $this->transaction(function () use ($command): Aisle {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateAisleCommand $cmd): Aisle {
                    $existing = $this->repository->findByZone($cmd->tenantId, $cmd->zoneId);
                    foreach ($existing as $aisle) {
                        if (strtolower($aisle->code) === strtolower($cmd->code)) {
                            throw new \DomainException(
                                "An aisle with code '{$cmd->code}' already exists in this zone."
                            );
                        }
                    }

                    return $this->repository->save(new Aisle(
                        id: null,
                        tenantId: $cmd->tenantId,
                        zoneId: $cmd->zoneId,
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
