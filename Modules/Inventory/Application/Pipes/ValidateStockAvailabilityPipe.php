<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Pipes;

use Closure;
use Modules\Inventory\Application\Commands\AdjustStockCommand;
use Modules\Inventory\Application\Commands\ReserveStockCommand;
use Modules\Inventory\Application\Commands\ShipStockCommand;
use Modules\Inventory\Application\Commands\TransferStockCommand;
use Modules\Inventory\Domain\Contracts\StockLedgerRepositoryInterface;
use Modules\Inventory\Domain\Enums\TransactionType;

/**
 * Pipeline pipe that validates sufficient stock before a deduction, shipment, transfer, or reservation.
 * Passes through unchanged for inbound transactions (receipt, adjustment_in, etc.).
 */
class ValidateStockAvailabilityPipe
{
    public function __construct(
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
    ) {}

    public function handle(AdjustStockCommand|TransferStockCommand|ShipStockCommand|ReserveStockCommand $command, Closure $next): mixed
    {
        $warehouseId = $command instanceof TransferStockCommand
            ? $command->sourceWarehouseId
            : $command->warehouseId;

        $mustValidate = match (true) {
            $command instanceof AdjustStockCommand => ! TransactionType::from($command->adjustmentType)->isPositive(),
            $command instanceof TransferStockCommand => true,
            $command instanceof ShipStockCommand => true,
            $command instanceof ReserveStockCommand => true,
        };

        if ($mustValidate) {
            $balance = $this->stockLedgerRepository->lockBalance(
                $command->tenantId,
                $warehouseId,
                $command->productId,
            );

            $available = $balance?->availableQuantity() ?? '0.0000';

            if (bccomp($command->quantity, $available, 4) > 0) {
                throw new \DomainException(
                    "Insufficient stock. Available: {$available}, Requested: {$command->quantity}."
                );
            }
        }

        return $next($command);
    }
}
