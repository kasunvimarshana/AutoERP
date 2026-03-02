<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Commands\TransferStockCommand;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use Modules\Inventory\Domain\Entities\StockLedgerEntry;
use Modules\Inventory\Domain\Enums\LedgerEntryType;

class TransferStockHandler
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventory
    ) {}

    /**
     * Handle a stock transfer between warehouses.
     * Creates OUT entry in source warehouse and IN entry in destination warehouse atomically.
     *
     * @throws \DomainException If source warehouse has insufficient stock.
     */
    public function handle(TransferStockCommand $command): array
    {
        if ($command->warehouseFromId === $command->warehouseToId) {
            throw new \DomainException('Source and destination warehouses must be different.');
        }

        return DB::transaction(function () use ($command): array {
            $currentStock = $this->inventory->getStockLevelForUpdate(
                $command->productId,
                $command->warehouseFromId,
                $command->tenantId
            );

            if (bccomp($currentStock, $command->quantity, 4) < 0) {
                throw new \DomainException(
                    "Insufficient stock in source warehouse. Available: {$currentStock}, requested: {$command->quantity}."
                );
            }

            $outEntry = new StockLedgerEntry(
                id: 0,
                tenantId: $command->tenantId,
                productId: $command->productId,
                variantId: $command->variantId,
                warehouseId: $command->warehouseFromId,
                type: LedgerEntryType::TRANSFER_OUT,
                quantity: bcadd($command->quantity, '0', 4),
                unitCost: bcadd($command->unitCost, '0', 4),
                referenceType: 'transfer',
                referenceId: null,
                notes: $command->notes,
                createdAt: new DateTimeImmutable(),
            );

            $inEntry = new StockLedgerEntry(
                id: 0,
                tenantId: $command->tenantId,
                productId: $command->productId,
                variantId: $command->variantId,
                warehouseId: $command->warehouseToId,
                type: LedgerEntryType::TRANSFER_IN,
                quantity: bcadd($command->quantity, '0', 4),
                unitCost: bcadd($command->unitCost, '0', 4),
                referenceType: 'transfer',
                referenceId: null,
                notes: $command->notes,
                createdAt: new DateTimeImmutable(),
            );

            $savedOut = $this->inventory->recordEntry($outEntry);
            $savedIn  = $this->inventory->recordEntry($inEntry);

            return ['out' => $savedOut, 'in' => $savedIn];
        });
    }
}
