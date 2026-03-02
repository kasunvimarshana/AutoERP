<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\AdjustStockCommand;
use Modules\Inventory\Application\Pipes\ValidateStockAvailabilityPipe;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockBalance;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\TransactionType;

class AdjustStockHandler extends BaseHandler
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(AdjustStockCommand $command): StockLedgerEntry
    {
        return $this->transaction(function () use ($command): StockLedgerEntry {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                    ValidateStockAvailabilityPipe::class,
                ])
                ->then(function (AdjustStockCommand $cmd): StockLedgerEntry {
                    $type = TransactionType::from($cmd->adjustmentType);
                    $totalCost = bcmul($cmd->quantity, $cmd->unitCost, 4);

                    $entry = new StockLedgerEntry(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->warehouseId,
                        productId: $cmd->productId,
                        transactionType: $type->value,
                        quantity: $cmd->quantity,
                        unitCost: $cmd->unitCost,
                        totalCost: $totalCost,
                        referenceType: null,
                        referenceId: null,
                        notes: $cmd->notes,
                        createdAt: null,
                    );

                    $saved = $this->stockLedgerRepository->appendEntry($entry);
                    $this->applyBalanceChange($cmd, $type);

                    return $saved;
                });
        });
    }

    private function applyBalanceChange(AdjustStockCommand $cmd, TransactionType $type): void
    {
        $balance = $this->stockLedgerRepository->lockBalance($cmd->tenantId, $cmd->warehouseId, $cmd->productId);

        $currentQty = $balance?->quantityOnHand ?? '0.0000';
        $reservedQty = $balance?->quantityReserved ?? '0.0000';
        $currentCost = $balance?->averageCost ?? $cmd->unitCost;

        if ($type->isPositive()) {
            $newQty = bcadd($currentQty, $cmd->quantity, 4);
            $totalValue = bcadd(
                bcmul($currentQty, $currentCost, 4),
                bcmul($cmd->quantity, $cmd->unitCost, 4),
                4
            );
            $avgCost = bccomp($newQty, '0', 4) > 0
                ? bcdiv($totalValue, $newQty, 4)
                : '0.0000';
        } else {
            $newQty = bcsub($currentQty, $cmd->quantity, 4);
            $avgCost = $currentCost;
        }

        $newBalance = new StockBalance(
            id: $balance?->id,
            tenantId: $cmd->tenantId,
            warehouseId: $cmd->warehouseId,
            productId: $cmd->productId,
            quantityOnHand: $newQty,
            quantityReserved: $reservedQty,
            averageCost: $avgCost,
            updatedAt: null,
        );

        $this->stockLedgerRepository->saveBalance($newBalance);
    }
}
