<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Commands\AdjustStockCommand;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\LedgerEntryType;

class AdjustStockHandler
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventory
    ) {}

    /**
     * Handle a stock adjustment command.
     * Uses pessimistic locking to prevent race conditions.
     *
     * @throws \DomainException If the adjustment would result in negative stock.
     */
    public function handle(AdjustStockCommand $command): StockLedgerEntry
    {
        $type = LedgerEntryType::from($command->type);

        return DB::transaction(function () use ($command, $type): StockLedgerEntry {
            // Acquire pessimistic lock before reading current stock
            $currentStock = $this->inventory->getStockLevelForUpdate(
                $command->productId,
                $command->warehouseId,
                $command->tenantId
            );

            // Calculate new balance and validate for outbound adjustments
            $newBalance = $type->isInbound()
                ? bcadd($currentStock, $command->quantity, 4)
                : bcsub($currentStock, $command->quantity, 4);

            if ($type->isOutbound() && bccomp($newBalance, '0', 4) < 0) {
                throw new \DomainException(
                    "Insufficient stock. Available: {$currentStock}, requested: {$command->quantity}."
                );
            }

            $entry = new StockLedgerEntry(
                id: 0,
                tenantId: $command->tenantId,
                productId: $command->productId,
                variantId: $command->variantId,
                warehouseId: $command->warehouseId,
                type: $type,
                quantity: bcadd($command->quantity, '0', 4),
                unitCost: bcadd($command->unitCost, '0', 4),
                referenceType: 'adjustment',
                referenceId: $command->referenceId,
                notes: $command->reason,
                createdAt: new DateTimeImmutable(),
            );

            return $this->inventory->recordEntry($entry);
        });
    }
}
