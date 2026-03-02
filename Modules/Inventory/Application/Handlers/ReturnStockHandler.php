<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\ReturnStockCommand;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\TransactionType;

class ReturnStockHandler extends BaseHandler
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(ReturnStockCommand $command): StockLedgerEntry
    {
        return $this->transaction(function () use ($command): StockLedgerEntry {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (ReturnStockCommand $cmd): StockLedgerEntry {
                    $totalCost = bcmul($cmd->quantity, $cmd->unitCost, 4);

                    $entry = new StockLedgerEntry(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->warehouseId,
                        productId: $cmd->productId,
                        transactionType: TransactionType::ReturnIn->value,
                        quantity: $cmd->quantity,
                        unitCost: $cmd->unitCost,
                        totalCost: $totalCost,
                        referenceType: $cmd->referenceType,
                        referenceId: $cmd->referenceId,
                        notes: $cmd->notes,
                        createdAt: null,
                    );

                    $saved = $this->stockLedgerRepository->appendEntry($entry);
                    $this->updateBalance($cmd);

                    return $saved;
                });
        });
    }

    private function updateBalance(ReturnStockCommand $cmd): void
    {
        $balance = $this->stockLedgerRepository->lockBalance($cmd->tenantId, $cmd->warehouseId, $cmd->productId);

        if ($balance === null) {
            $this->stockLedgerRepository->saveBalance(new StockBalance(
                id: null,
                tenantId: $cmd->tenantId,
                warehouseId: $cmd->warehouseId,
                productId: $cmd->productId,
                quantityOnHand: $cmd->quantity,
                quantityReserved: '0.0000',
                averageCost: $cmd->unitCost,
                updatedAt: null,
            ));

            return;
        }

        $newQty = bcadd($balance->quantityOnHand, $cmd->quantity, 4);
        $totalValue = bcadd(
            bcmul($balance->quantityOnHand, $balance->averageCost, 4),
            bcmul($cmd->quantity, $cmd->unitCost, 4),
            4
        );
        $avgCost = bccomp($newQty, '0', 4) > 0
            ? bcdiv($totalValue, $newQty, 4)
            : '0.0000';

        $this->stockLedgerRepository->saveBalance(new StockBalance(
            id: $balance->id,
            tenantId: $cmd->tenantId,
            warehouseId: $cmd->warehouseId,
            productId: $cmd->productId,
            quantityOnHand: $newQty,
            quantityReserved: $balance->quantityReserved,
            averageCost: $avgCost,
            updatedAt: null,
        ));
    }
}
