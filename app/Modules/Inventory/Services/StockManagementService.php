<?php

namespace App\Modules\Inventory\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use App\Modules\Inventory\Models\StockLedger;
use App\Modules\Inventory\Repositories\ProductRepository;
use App\Modules\Inventory\Repositories\StockLedgerRepository;

/**
 * Stock Management Service
 *
 * Handles stock movement operations with FIFO/FEFO logic.
 * All operations are append-only for immutable audit trails.
 */
class StockManagementService extends BaseService
{
    protected ProductRepository $productRepository;

    public function __construct(
        StockLedgerRepository $repository,
        ProductRepository $productRepository
    ) {
        parent::__construct($repository);
        $this->productRepository = $productRepository;
    }

    /**
     * Record incoming stock (purchase, transfer in, etc.)
     */
    public function recordIncomingStock(array $data): StockLedger
    {
        return $this->transaction(function () use ($data) {
            // Validate product exists
            $product = $this->productRepository->find($data['product_id']);
            if (! $product) {
                throw new ServiceException('Product not found');
            }

            // Validate quantity is positive
            if ($data['quantity'] <= 0) {
                throw new ServiceException('Quantity must be positive for incoming stock');
            }

            // Ensure transaction type is incoming
            if (! in_array($data['transaction_type'], ['purchase', 'transfer_in', 'adjustment_in', 'return', 'production'])) {
                throw new ServiceException('Invalid transaction type for incoming stock');
            }

            return $this->repository->recordMovement($data);
        });
    }

    /**
     * Record outgoing stock (sale, transfer out, etc.) with FIFO/FEFO.
     *
     * @return array Array of StockLedger entries
     */
    public function recordOutgoingStock(array $data): array
    {
        return $this->transaction(function () use ($data) {
            $product = $this->productRepository->find($data['product_id']);
            if (! $product) {
                throw new ServiceException('Product not found');
            }

            // Validate quantity is positive
            if ($data['quantity'] <= 0) {
                throw new ServiceException('Quantity must be positive for outgoing stock');
            }

            // Check available stock
            $availableStock = $this->repository->getCurrentStock(
                $data['product_id'],
                $data['branch_id'],
                $data['warehouse_id'] ?? null
            );

            if ($availableStock < $data['quantity']) {
                throw new ServiceException(
                    "Insufficient stock. Available: {$availableStock}, Required: {$data['quantity']}"
                );
            }

            // Apply FIFO/FEFO logic
            $entries = [];
            $remainingQty = $data['quantity'];

            // Use FEFO if product tracks expiry, otherwise use FIFO
            if ($product->track_expiry) {
                $batches = $this->repository->getStockByExpiry(
                    $data['product_id'],
                    $data['branch_id'],
                    $data['warehouse_id'] ?? null
                );
            } else {
                $batches = $this->repository->getStockByBatch(
                    $data['product_id'],
                    $data['branch_id'],
                    $data['warehouse_id'] ?? null
                );
            }

            foreach ($batches as $batch) {
                if ($remainingQty <= 0) {
                    break;
                }

                $qtyToDeduct = min($remainingQty, $batch->current_quantity);

                $ledgerData = array_merge($data, [
                    'quantity' => $qtyToDeduct,
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                    'expiry_date' => $batch->expiry_date,
                    'unit_cost' => $batch->average_cost,
                ]);

                $entries[] = $this->repository->recordMovement($ledgerData);
                $remainingQty -= $qtyToDeduct;
            }

            if ($remainingQty > 0) {
                throw new ServiceException('Unable to allocate stock from batches');
            }

            return $entries;
        });
    }

    /**
     * Get current stock level.
     */
    public function getCurrentStockLevel(int $productId, int $branchId, ?int $warehouseId = null): float
    {
        return $this->repository->getCurrentStock($productId, $branchId, $warehouseId);
    }

    /**
     * Get stock valuation.
     */
    public function getStockValuation(int $productId, int $branchId, ?int $warehouseId = null): array
    {
        $batches = $this->repository->getStockByBatch($productId, $branchId, $warehouseId);

        $totalQty = 0;
        $totalValue = 0;

        foreach ($batches as $batch) {
            $totalQty += $batch->current_quantity;
            $totalValue += $batch->current_quantity * $batch->average_cost;
        }

        return [
            'quantity' => $totalQty,
            'total_value' => $totalValue,
            'average_cost' => $totalQty > 0 ? $totalValue / $totalQty : 0,
        ];
    }

    /**
     * Get expiry alerts.
     */
    public function getExpiryAlerts(int $branchId, int $days = 30): array
    {
        return [
            'expired' => $this->repository->getExpiredStock($branchId),
            'near_expiry' => $this->repository->getNearExpiryStock($days, $branchId),
        ];
    }
}
