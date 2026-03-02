<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\ShipStockCommand;
use Modules\Inventory\Application\Pipes\ValidateStockAvailabilityPipe;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\TransactionType;

class ShipStockHandler extends BaseHandler
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(ShipStockCommand $command): StockLedgerEntry
    {
        return $this->transaction(function () use ($command): StockLedgerEntry {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                    ValidateStockAvailabilityPipe::class,
                ])
                ->then(function (ShipStockCommand $cmd): StockLedgerEntry {
                    $totalCost = bcmul($cmd->quantity, $cmd->unitCost, 4);

                    $entry = new StockLedgerEntry(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->warehouseId,
                        productId: $cmd->productId,
                        transactionType: TransactionType::Shipment->value,
                        quantity: $cmd->quantity,
                        unitCost: $cmd->unitCost,
                        totalCost: $totalCost,
                        referenceType: $cmd->referenceType,
                        referenceId: $cmd->referenceId,
                        notes: $cmd->notes,
                        createdAt: null,
                    );

                    $saved = $this->stockLedgerRepository->appendEntry($entry);
                    $this->deductBalance($cmd);

                    return $saved;
                });
        });
    }

    private function deductBalance(ShipStockCommand $cmd): void
    {
        $balance = $this->stockLedgerRepository->lockBalance($cmd->tenantId, $cmd->warehouseId, $cmd->productId);
        if ($balance === null) {
            return;
        }

        $newQty = bcsub($balance->quantityOnHand, $cmd->quantity, 4);

        $this->stockLedgerRepository->saveBalance(new StockBalance(
            id: $balance->id,
            tenantId: $cmd->tenantId,
            warehouseId: $cmd->warehouseId,
            productId: $cmd->productId,
            quantityOnHand: $newQty,
            quantityReserved: $balance->quantityReserved,
            averageCost: $balance->averageCost,
            updatedAt: null,
        ));
    }
}
