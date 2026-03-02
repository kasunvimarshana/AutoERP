<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Inventory\Application\DTOs\StockTransactionDTO;
use Modules\Inventory\Domain\Entities\StockTransaction;

/**
 * Inventory service contract.
 *
 * Defines the stock mutation methods that other modules (Sales, Procurement)
 * may call without coupling directly to the InventoryService implementation.
 *
 * Modules that declare `inventory` as a dependency in their module.json may
 * depend on this contract via constructor injection.
 */
interface InventoryServiceContract extends ServiceContract
{
    /**
     * Record a stock transaction and update the matching stock item ledger row.
     *
     * Handles all transaction types: purchase_receipt, sales_shipment,
     * internal_transfer, adjustment, return.
     *
     * All mutations are wrapped in a DB::transaction() with pessimistic locking.
     * Outbound transactions (sales_shipment, internal_transfer) throw
     * InvalidArgumentException if the result would be negative stock.
     *
     * @throws \InvalidArgumentException if pharmaceutical compliance fields are
     *                                   missing or if the transaction would result
     *                                   in negative stock.
     */
    public function recordTransaction(StockTransactionDTO $dto): StockTransaction;

    /**
     * Deduct stock across multiple batches using the specified costing strategy.
     *
     * Supported strategies: 'fifo', 'lifo', 'fefo', 'manual'.
     *
     * Each batch touched produces one immutable StockTransaction ledger entry.
     *
     * @return array<int, array{batch_number: string|null, quantity_deducted: string, transaction_id: int}>
     *
     * @throws \InvalidArgumentException if the manual strategy is used without a
     *                                   batch_number, or if total available stock
     *                                   is insufficient.
     */
    public function deductByStrategy(
        int $productId,
        int $warehouseId,
        int $uomId,
        string $quantity,
        string $unitCost,
        string $strategy = 'fifo',
        ?string $batchNumber = null,
        ?string $notes = null,
        bool $isPharmaceuticalCompliant = false,
    ): array;
}
