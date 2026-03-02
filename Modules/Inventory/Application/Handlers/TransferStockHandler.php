<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\TransferStockCommand;
use Modules\Inventory\Application\Pipes\ValidateStockAvailabilityPipe;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\TransactionType;

class TransferStockHandler extends BaseHandler
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(TransferStockCommand $command): array
    {
        return $this->transaction(function () use ($command): array {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                    ValidateStockAvailabilityPipe::class,
                ])
                ->then(function (TransferStockCommand $cmd): array {
                    $totalCost = bcmul($cmd->quantity, $cmd->unitCost, 4);

                    $outEntry = new StockLedgerEntry(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->sourceWarehouseId,
                        productId: $cmd->productId,
                        transactionType: TransactionType::TransferOut->value,
                        quantity: $cmd->quantity,
                        unitCost: $cmd->unitCost,
                        totalCost: $totalCost,
                        referenceType: 'transfer',
                        referenceId: null,
                        notes: $cmd->notes,
                        createdAt: null,
                    );

                    $inEntry = new StockLedgerEntry(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->destinationWarehouseId,
                        productId: $cmd->productId,
                        transactionType: TransactionType::TransferIn->value,
                        quantity: $cmd->quantity,
                        unitCost: $cmd->unitCost,
                        totalCost: $totalCost,
                        referenceType: 'transfer',
                        referenceId: null,
                        notes: $cmd->notes,
                        createdAt: null,
                    );

                    $savedOut = $this->stockLedgerRepository->appendEntry($outEntry);
                    $savedIn = $this->stockLedgerRepository->appendEntry($inEntry);

                    $this->deductFromSource($cmd);
                    $this->addToDestination($cmd);

                    return ['transfer_out' => $savedOut, 'transfer_in' => $savedIn];
                });
        });
    }

    private function deductFromSource(TransferStockCommand $cmd): void
    {
        $balance = $this->stockLedgerRepository->lockBalance($cmd->tenantId, $cmd->sourceWarehouseId, $cmd->productId);
        if ($balance === null) {
            return;
        }

        $newQty = bcsub($balance->quantityOnHand, $cmd->quantity, 4);

        $this->stockLedgerRepository->saveBalance(new StockBalance(
            id: $balance->id,
            tenantId: $cmd->tenantId,
            warehouseId: $cmd->sourceWarehouseId,
            productId: $cmd->productId,
            quantityOnHand: $newQty,
            quantityReserved: $balance->quantityReserved,
            averageCost: $balance->averageCost,
            updatedAt: null,
        ));
    }

    private function addToDestination(TransferStockCommand $cmd): void
    {
        $balance = $this->stockLedgerRepository->lockBalance($cmd->tenantId, $cmd->destinationWarehouseId, $cmd->productId);

        if ($balance === null) {
            $this->stockLedgerRepository->saveBalance(new StockBalance(
                id: null,
                tenantId: $cmd->tenantId,
                warehouseId: $cmd->destinationWarehouseId,
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
            warehouseId: $cmd->destinationWarehouseId,
            productId: $cmd->productId,
            quantityOnHand: $newQty,
            quantityReserved: $balance->quantityReserved,
            averageCost: $avgCost,
            updatedAt: null,
        ));
    }
}
