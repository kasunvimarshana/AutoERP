<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\UpdateLotCommand;
use Modules\Inventory\Domain\Contracts\LotRepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryLot;

class UpdateLotHandler extends BaseHandler
{
    public function __construct(
        private readonly LotRepositoryInterface $lotRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateLotCommand $command): InventoryLot
    {
        return $this->transaction(function () use ($command): InventoryLot {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateLotCommand $cmd): InventoryLot {
                    $existing = $this->lotRepository->findById($cmd->tenantId, $cmd->id);

                    if ($existing === null) {
                        throw new \DomainException("Inventory lot [{$cmd->id}] not found.");
                    }

                    return $this->lotRepository->save(new InventoryLot(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        productId: $existing->productId,
                        warehouseId: $existing->warehouseId,
                        lotNumber: $cmd->lotNumber,
                        serialNumber: $cmd->serialNumber,
                        batchNumber: $cmd->batchNumber,
                        manufacturedDate: $cmd->manufacturedDate,
                        expiryDate: $cmd->expiryDate,
                        quantity: $cmd->quantity,
                        notes: $cmd->notes,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
