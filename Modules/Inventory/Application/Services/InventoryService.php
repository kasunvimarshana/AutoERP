<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Inventory\Application\DTOs\StockBatchDTO;
use Modules\Inventory\Application\DTOs\StockTransactionDTO;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use Modules\Inventory\Domain\Contracts\InventoryServiceContract;
use Modules\Inventory\Domain\Entities\StockItem;
use Modules\Inventory\Domain\Entities\StockReservation;
use Modules\Inventory\Domain\Entities\StockTransaction;

/**
 * Inventory service.
 *
 * Orchestrates all inventory use cases: transaction recording, stock level queries,
 * atomic stock reservations, and batch/lot management.
 *
 * All quantity/cost arithmetic uses DecimalHelper (BCMath). No floating-point.
 * All mutations are wrapped in DB::transaction() with pessimistic locking.
 */
class InventoryService implements ServiceContract, InventoryServiceContract
{
    public function __construct(
        private readonly InventoryRepositoryContract $repository,
    ) {}

    /**
     * Record a stock transaction and update the stock item ledger.
     *
     * Wraps everything in a DB transaction with pessimistic locking on the stock item
     * to prevent concurrent over-deduction or double-reservation.
     *
     * Negative stock is strictly prevented: an outbound transaction that would reduce
     * quantity_on_hand below zero throws an InvalidArgumentException.
     *
     * @throws InvalidArgumentException if pharmaceutical compliance fields are missing
     *                                  or if the transaction would result in negative stock.
     */
    public function recordTransaction(StockTransactionDTO $dto): StockTransaction
    {
        if ($dto->isPharmaceuticalCompliant) {
            if (empty($dto->batchNumber)) {
                throw new InvalidArgumentException('batch_number is required for pharmaceutical-compliant transactions.');
            }
            if (empty($dto->expiryDate)) {
                throw new InvalidArgumentException('expiry_date is required for pharmaceutical-compliant transactions.');
            }
        }

        return DB::transaction(function () use ($dto): StockTransaction {
            // Pessimistic lock on the matching stock item row to prevent race conditions.
            $stockItem = StockItem::query()
                ->where('warehouse_id', $dto->warehouseId)
                ->where('product_id', $dto->productId)
                ->where('uom_id', $dto->uomId)
                ->when($dto->batchNumber, fn ($q) => $q->where('batch_number', $dto->batchNumber))
                ->when($dto->serialNumber, fn ($q) => $q->where('serial_number', $dto->serialNumber))
                ->lockForUpdate()
                ->first();

            $totalCost = DecimalHelper::mul($dto->quantity, $dto->unitCost, DecimalHelper::SCALE_STANDARD);

            // Determine whether this is an inbound or outbound movement.
            $isOutbound = in_array($dto->transactionType, ['sales_shipment', 'internal_transfer'], true);

            if ($stockItem !== null) {
                if ($isOutbound) {
                    $newOnHand = DecimalHelper::sub($stockItem->quantity_on_hand, $dto->quantity);

                    // Negative stock prevention: reject outbound transactions that would
                    // reduce on-hand quantity below zero.
                    if (DecimalHelper::lessThan($newOnHand, '0')) {
                        throw new InvalidArgumentException(
                            sprintf(
                                'Insufficient stock: transaction would result in negative on-hand quantity. '
                                . 'Available: %s, Requested: %s.',
                                $stockItem->quantity_on_hand,
                                $dto->quantity
                            )
                        );
                    }
                } else {
                    $newOnHand = DecimalHelper::add($stockItem->quantity_on_hand, $dto->quantity);
                }

                $newAvailable = DecimalHelper::sub($newOnHand, $stockItem->quantity_reserved);

                $stockItem->update([
                    'quantity_on_hand'   => $newOnHand,
                    'quantity_available' => $newAvailable,
                    'cost_price'         => $dto->unitCost,
                ]);
            } else {
                // For outbound transactions with no existing stock item, prevent creation
                // of a negative stock record.
                if ($isOutbound) {
                    throw new InvalidArgumentException(
                        'Insufficient stock: no stock item found for the specified product/warehouse/batch combination.'
                    );
                }

                // Create a new stock item record for this product/warehouse/batch combination.
                StockItem::create([
                    'warehouse_id'       => $dto->warehouseId,
                    'product_id'         => $dto->productId,
                    'uom_id'             => $dto->uomId,
                    'batch_number'       => $dto->batchNumber,
                    'lot_number'         => $dto->lotNumber,
                    'serial_number'      => $dto->serialNumber,
                    'expiry_date'        => $dto->expiryDate,
                    'quantity_on_hand'   => $dto->quantity,
                    'quantity_reserved'  => '0.0000',
                    'quantity_available' => $dto->quantity,
                    'costing_method'     => 'fifo',
                    'cost_price'         => $dto->unitCost,
                ]);
            }

            // Persist the immutable transaction ledger entry.
            return StockTransaction::create([
                'transaction_type'            => $dto->transactionType,
                'warehouse_id'                => $dto->warehouseId,
                'product_id'                  => $dto->productId,
                'uom_id'                      => $dto->uomId,
                'quantity'                    => $dto->quantity,
                'unit_cost'                   => $dto->unitCost,
                'total_cost'                  => $totalCost,
                'batch_number'                => $dto->batchNumber,
                'lot_number'                  => $dto->lotNumber,
                'serial_number'               => $dto->serialNumber,
                'expiry_date'                 => $dto->expiryDate,
                'notes'                       => $dto->notes,
                'transacted_at'               => now(),
                'transacted_by'               => auth()->id(),
                'is_pharmaceutical_compliant' => $dto->isPharmaceuticalCompliant,
            ]);
        });
    }

    /**
     * Deduct stock across multiple batches using the specified costing strategy.
     *
     * Supported strategies: 'fifo' (oldest batch first), 'lifo' (newest batch first),
     * 'fefo' (nearest expiry first — mandatory in pharmaceutical compliance mode),
     * 'manual' (caller must specify batch_number in the DTO).
     *
     * NOTE: The manual-strategy batch_number requirement is validated **before** the
     * DB transaction begins, so it throws immediately without acquiring any locks.
     *
     * Each batch deduction is recorded as an individual StockTransaction entry.
     *
     * @return array<int, array{batch_number: string|null, quantity_deducted: string, transaction_id: int}>
     *
     * @throws InvalidArgumentException if the manual strategy is used without a batch_number,
     *                                  or if total available stock is insufficient.
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
    ): array {
        // Validate manual strategy requirements before entering the DB transaction.
        if ($strategy === 'manual' && $batchNumber === null) {
            throw new InvalidArgumentException(
                'batch_number is required when using the manual deduction strategy.'
            );
        }

        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $uomId,
            $quantity,
            $unitCost,
            $strategy,
            $batchNumber,
            $notes,
            $isPharmaceuticalCompliant,
        ): array {
            // Select batches using the requested strategy.
            if ($strategy === 'manual') {
                $batches = StockItem::query()
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('batch_number', $batchNumber)
                    ->where('quantity_available', '>', '0')
                    ->lockForUpdate()
                    ->get();
            } else {
                $batches = match ($strategy) {
                    'lifo'  => $this->repository->findByLIFO($productId, $warehouseId),
                    'fefo'  => $this->repository->findByFEFO($productId, $warehouseId),
                    default => $this->repository->findByFIFO($productId, $warehouseId),
                };

                // Re-acquire locks now that we know which rows we need.
                $batchIds = $batches->pluck('id')->all();
                $batches  = StockItem::query()
                    ->whereIn('id', $batchIds)
                    ->where('quantity_available', '>', '0')
                    ->orderBy('created_at', $strategy === 'lifo' ? 'desc' : 'asc')
                    ->lockForUpdate()
                    ->get();
            }

            // Validate that sufficient total stock exists.
            $totalAvailable = '0.0000';
            foreach ($batches as $batch) {
                $totalAvailable = DecimalHelper::add($totalAvailable, $batch->quantity_available);
            }

            if (DecimalHelper::greaterThan($quantity, $totalAvailable)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Insufficient stock: requested %s but only %s available across all batches.',
                        $quantity,
                        $totalAvailable
                    )
                );
            }

            // Deduct across batches in order, recording a transaction per batch touched.
            $remaining  = $quantity;
            $deductions = [];

            foreach ($batches as $batch) {
                if (DecimalHelper::lessThanOrEqual($remaining, '0')) {
                    break;
                }

                $deductQty = DecimalHelper::lessThanOrEqual($remaining, $batch->quantity_available)
                    ? $remaining
                    : $batch->quantity_available;

                $newOnHand    = DecimalHelper::sub($batch->quantity_on_hand, $deductQty);
                $newAvailable = DecimalHelper::sub($batch->quantity_available, $deductQty);

                $batch->update([
                    'quantity_on_hand'   => $newOnHand,
                    'quantity_available' => $newAvailable,
                ]);

                $totalCost = DecimalHelper::mul($deductQty, $unitCost, DecimalHelper::SCALE_STANDARD);

                $transaction = StockTransaction::create([
                    'transaction_type'            => 'sales_shipment',
                    'warehouse_id'                => $warehouseId,
                    'product_id'                  => $productId,
                    'uom_id'                      => $uomId,
                    'quantity'                    => $deductQty,
                    'unit_cost'                   => $unitCost,
                    'total_cost'                  => $totalCost,
                    'batch_number'                => $batch->batch_number,
                    'lot_number'                  => $batch->lot_number,
                    'serial_number'               => $batch->serial_number,
                    'expiry_date'                 => $batch->expiry_date,
                    'notes'                       => $notes,
                    'transacted_at'               => now(),
                    'transacted_by'               => auth()->id(),
                    'is_pharmaceutical_compliant' => $isPharmaceuticalCompliant,
                ]);

                $deductions[] = [
                    'batch_number'      => $batch->batch_number,
                    'quantity_deducted' => $deductQty,
                    'transaction_id'    => $transaction->id,
                ];

                $remaining = DecimalHelper::sub($remaining, $deductQty);
            }

            return $deductions;
        });
    }

    /**
     * Create a new batch (stock item) record directly.
     *
     * Used when a purchase receipt creates a new batch with known quantity,
     * cost price, lot number, and expiry date. Records a purchase_receipt
     * transaction in the ledger.
     *
     * @throws InvalidArgumentException if quantity is not positive.
     */
    public function createBatch(StockBatchDTO $dto): StockItem
    {
        if (DecimalHelper::lessThanOrEqual($dto->quantity, '0')) {
            throw new InvalidArgumentException('Batch quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($dto): StockItem {
            /** @var StockItem $item */
            $item = StockItem::create([
                'warehouse_id'       => $dto->warehouseId,
                'stock_location_id'  => $dto->stockLocationId,
                'product_id'         => $dto->productId,
                'uom_id'             => $dto->uomId,
                'batch_number'       => $dto->batchNumber,
                'lot_number'         => $dto->lotNumber,
                'serial_number'      => $dto->serialNumber,
                'expiry_date'        => $dto->expiryDate,
                'quantity_on_hand'   => $dto->quantity,
                'quantity_reserved'  => '0.0000',
                'quantity_available' => $dto->quantity,
                'costing_method'     => $dto->costingMethod,
                'cost_price'         => $dto->costPrice,
            ]);

            // Record an immutable purchase_receipt ledger entry for traceability.
            StockTransaction::create([
                'transaction_type'            => 'purchase_receipt',
                'warehouse_id'                => $dto->warehouseId,
                'product_id'                  => $dto->productId,
                'uom_id'                      => $dto->uomId,
                'quantity'                    => $dto->quantity,
                'unit_cost'                   => $dto->costPrice,
                'total_cost'                  => DecimalHelper::mul(
                    $dto->quantity,
                    $dto->costPrice,
                    DecimalHelper::SCALE_STANDARD
                ),
                'batch_number'                => $dto->batchNumber,
                'lot_number'                  => $dto->lotNumber,
                'serial_number'               => $dto->serialNumber,
                'expiry_date'                 => $dto->expiryDate,
                'notes'                       => 'Batch created via direct batch management.',
                'transacted_at'               => now(),
                'transacted_by'               => auth()->id(),
                'is_pharmaceutical_compliant' => false,
            ]);

            return $item;
        });
    }

    /**
     * Show a single batch (stock item) by its primary key.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function showBatch(int $id): StockItem
    {
        return $this->repository->findStockItemById($id);
    }

    /**
     * Update a batch record's mutable fields.
     *
     * Only the following fields may be updated after creation:
     * cost_price, expiry_date, lot_number, batch_number, costing_method.
     *
     * @param array<string, mixed> $data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateBatch(int $id, array $data): StockItem
    {
        return DB::transaction(function () use ($id, $data): StockItem {
            $allowed = ['cost_price', 'expiry_date', 'lot_number', 'batch_number', 'costing_method'];
            $update  = array_intersect_key($data, array_flip($allowed));

            return $this->repository->updateStockItem($id, $update);
        });
    }

    /**
     * Delete a batch (stock item) record.
     *
     * Only batches with zero on-hand and reserved quantities may be deleted.
     *
     * @throws InvalidArgumentException if the batch has remaining stock.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteBatch(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $item = $this->repository->findStockItemById($id);

            if (
                DecimalHelper::greaterThan($item->quantity_on_hand, '0') ||
                DecimalHelper::greaterThan($item->quantity_reserved, '0')
            ) {
                throw new InvalidArgumentException(
                    'Cannot delete a batch that still has stock on hand or reserved quantity.'
                );
            }

            return $this->repository->deleteStockItem($id);
        });
    }

    /**
     * Return aggregated stock level for a product in a warehouse.
     *
     * @return array{quantity_on_hand: string, quantity_reserved: string, quantity_available: string}
     */
    public function getStockLevel(int $productId, int $warehouseId): array
    {
        $items = $this->repository->findByProduct($productId)
            ->where('warehouse_id', $warehouseId);

        $onHand    = '0.0000';
        $reserved  = '0.0000';
        $available = '0.0000';

        foreach ($items as $item) {
            $onHand    = DecimalHelper::add($onHand, $item->quantity_on_hand);
            $reserved  = DecimalHelper::add($reserved, $item->quantity_reserved);
            $available = DecimalHelper::add($available, $item->quantity_available);
        }

        return [
            'quantity_on_hand'   => $onHand,
            'quantity_reserved'  => $reserved,
            'quantity_available' => $available,
        ];
    }

    /**
     * Reserve stock for a reference (e.g. a sales order) atomically.
     *
     * Uses pessimistic locking on the stock item to ensure the reservation
     * does not exceed available quantity.
     *
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException if insufficient stock is available.
     */
    public function reserve(array $data): StockReservation
    {
        return DB::transaction(function () use ($data): StockReservation {
            $stockItem = StockItem::query()
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $requestedQty = (string) $data['quantity_reserved'];

            if (DecimalHelper::greaterThan($requestedQty, $stockItem->quantity_available)) {
                throw new InvalidArgumentException('Insufficient available stock for reservation.');
            }

            $newReserved   = DecimalHelper::add($stockItem->quantity_reserved, $requestedQty);
            $newAvailable  = DecimalHelper::sub($stockItem->quantity_available, $requestedQty);

            $stockItem->update([
                'quantity_reserved'  => $newReserved,
                'quantity_available' => $newAvailable,
            ]);

            return StockReservation::create([
                'product_id'        => $data['product_id'],
                'warehouse_id'      => $data['warehouse_id'],
                'quantity_reserved' => $requestedQty,
                'reference_type'    => $data['reference_type'],
                'reference_id'      => $data['reference_id'],
                'expires_at'        => $data['expires_at'] ?? null,
                'is_fulfilled'      => false,
            ]);
        });
    }

    /**
     * Release a stock reservation.
     */
    public function releaseReservation(int|string $reservationId): bool
    {
        return DB::transaction(function () use ($reservationId): bool {
            return $this->repository->deleteReservation($reservationId);
        });
    }

    /**
     * Return stock items for a product in FEFO order (First-Expired, First-Out).
     *
     * FEFO is mandatory when pharmaceutical compliance mode is active for a tenant.
     * Stock items are ordered by expiry_date ascending so that items expiring soonest
     * are consumed first. Items without an expiry_date are excluded.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, StockItem>
     */
    public function getStockByFEFO(int $productId, int $warehouseId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findByFEFO($productId, $warehouseId);
    }

    /**
     * Return a paginated list of stock transactions for a product.
     */
    public function listTransactions(int $productId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->paginateTransactions($productId, $perPage);
    }

    /**
     * Return a paginated list of all stock items (tenant-scoped).
     */
    public function listStockItems(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->paginateStockItems($perPage);
    }
}
