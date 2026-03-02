<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\CreateWarehouseCommand;
use Modules\Inventory\Domain\Contracts\WarehouseRepositoryInterface;
use Modules\Inventory\Domain\Entities\Warehouse;

class CreateWarehouseHandler extends BaseHandler
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateWarehouseCommand $command): Warehouse
    {
        return $this->transaction(function () use ($command): Warehouse {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateWarehouseCommand $cmd): Warehouse {
                    $existing = $this->warehouseRepository->findByCode($cmd->code, $cmd->tenantId);

                    if ($existing !== null) {
                        throw new \DomainException(
                            "A warehouse with code '{$cmd->code}' already exists in this tenant."
                        );
                    }

                    $warehouse = new Warehouse(
                        id: null,
                        tenantId: $cmd->tenantId,
                        code: strtoupper($cmd->code),
                        name: $cmd->name,
                        address: $cmd->address,
                        status: $cmd->status,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->warehouseRepository->save($warehouse);
                });
        });
    }
}
