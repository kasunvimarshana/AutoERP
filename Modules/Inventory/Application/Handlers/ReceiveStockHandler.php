<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\ReceiveStockCommand;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\TransactionType;

class ReceiveStockHandler extends BaseHandler
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(ReceiveStockCommand $command): StockLedgerEntry
    {
        return $this->transaction(function () use ($command): StockLedgerEntry {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (ReceiveStockCommand $cmd): StockLedgerEntry {
                    $totalCost = bcmul($cmd->quantity, $cmd->unitCost, 4);

                    $entry = new StockLedgerEntry(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->warehouseId,
                        productId: $cmd->productId,
                        transactionType: TransactionType::Receipt->value,
                        quantity: $cmd->quantity,
                        unitCost: $cmd->unitCost,
                        totalCost: $totalCost,
                        referenceType: $cmd->referenceType,
                        referenceId: $cmd->referenceId,
                        notes: $cmd->notes,
                        createdAt: null,
                    );

                    $saved = $this->stockLedgerRepository->appendEntry($entry);
                    $this->updateBalance($cmd->tenantId, $cmd->warehouseId, $cmd->productId, $cmd->quantity, $cmd->unitCost, true);

                    return $saved;
                });
        });
    }

    private function updateBalance(int $tenantId, int $warehouseId, int $productId, string $quantity, string $unitCost, bool $isInbound): void
    {
        $balance = $this->stockLedgerRepository->lockBalance($tenantId, $warehouseId, $productId);

        if ($balance === null) {
            $newBalance = new StockBalance(
                id: null,
                tenantId: $tenantId,
                warehouseId: $warehouseId,
                productId: $productId,
                quantityOnHand: $quantity,
                quantityReserved: '0.0000',
                averageCost: $unitCost,
                updatedAt: null,
            );
        } else {
            $newQty = bcadd($balance->quantityOnHand, $quantity, 4);
            $totalValue = bcadd(
                bcmul($balance->quantityOnHand, $balance->averageCost, 4),
                bcmul($quantity, $unitCost, 4),
                4
            );
            $avgCost = bccomp($newQty, '0', 4) > 0
                ? bcdiv($totalValue, $newQty, 4)
                : '0.0000';

            $newBalance = new StockBalance(
                id: $balance->id,
                tenantId: $tenantId,
                warehouseId: $warehouseId,
                productId: $productId,
                quantityOnHand: $newQty,
                quantityReserved: $balance->quantityReserved,
                averageCost: $avgCost,
                updatedAt: null,
            );
        }

        $this->stockLedgerRepository->saveBalance($newBalance);
    }
}
