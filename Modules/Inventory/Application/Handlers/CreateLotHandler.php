<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\CreateLotCommand;
use Modules\Inventory\Domain\Contracts\LotRepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryLot;

class CreateLotHandler extends BaseHandler
{
    public function __construct(
        private readonly LotRepositoryInterface $lotRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateLotCommand $command): InventoryLot
    {
        return $this->transaction(function () use ($command): InventoryLot {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateLotCommand $cmd): InventoryLot {
                    return $this->lotRepository->save(new InventoryLot(
                        id: null,
                        tenantId: $cmd->tenantId,
                        productId: $cmd->productId,
                        warehouseId: $cmd->warehouseId,
                        lotNumber: $cmd->lotNumber,
                        serialNumber: $cmd->serialNumber,
                        batchNumber: $cmd->batchNumber,
                        manufacturedDate: $cmd->manufacturedDate,
                        expiryDate: $cmd->expiryDate,
                        quantity: $cmd->quantity,
                        notes: $cmd->notes,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
