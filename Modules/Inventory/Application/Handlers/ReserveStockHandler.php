<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\ReserveStockCommand;
use Modules\Inventory\Application\Pipes\ValidateStockAvailabilityPipe;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;

class ReserveStockHandler extends BaseHandler
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * Reserve stock by incrementing quantity_reserved.
     * No ledger entry is created; the reservation is a soft hold.
     * Returns the updated StockBalance.
     */
    public function handle(ReserveStockCommand $command): StockBalance
    {
        return $this->transaction(function () use ($command): StockBalance {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                    ValidateStockAvailabilityPipe::class,
                ])
                ->then(function (ReserveStockCommand $cmd): StockBalance {
                    $balance = $this->stockLedgerRepository->lockBalance($cmd->tenantId, $cmd->warehouseId, $cmd->productId);

                    if ($balance === null) {
                        throw new \DomainException('No stock balance found for the requested product/warehouse.');
                    }

                    $newReserved = bcadd($balance->quantityReserved, $cmd->quantity, 4);

                    $updated = new StockBalance(
                        id: $balance->id,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->warehouseId,
                        productId: $cmd->productId,
                        quantityOnHand: $balance->quantityOnHand,
                        quantityReserved: $newReserved,
                        averageCost: $balance->averageCost,
                        updatedAt: null,
                    );

                    return $this->stockLedgerRepository->saveBalance($updated);
                });
        });
    }
}
