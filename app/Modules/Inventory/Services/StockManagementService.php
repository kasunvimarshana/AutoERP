<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\StockLedgerRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stock Management Service
 * 
 * Handles business logic for stock management operations
 */
class StockManagementService extends BaseService
{
    /**
     * Constructor
     *
     * @param StockLedgerRepository $repository
     */
    public function __construct(StockLedgerRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Record incoming stock (purchase, return, etc.)
     *
     * @param array $data
     * @return mixed
     */
    public function recordIncomingStock(array $data)
    {
        $data['quantity'] = abs($data['quantity']);
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        return $this->create($data);
    }

    /**
     * Record outgoing stock using FIFO method
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function recordOutgoingStock(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            $quantity = abs($data['quantity']);
            $batches = $this->repository->getFifoBatches(
                $data['product_id'],
                $data['warehouse_id']
            );

            $remainingQty = $quantity;
            foreach ($batches as $batch) {
                if ($remainingQty <= 0) {
                    break;
                }

                $deductQty = min($remainingQty, $batch->quantity);

                $this->create([
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity' => -$deductQty,
                    'unit_cost' => $batch->unit_cost,
                    'batch_number' => $batch->batch_number,
                    'transaction_type' => $data['transaction_type'] ?? 'sale',
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
                    'created_by' => $data['created_by'] ?? auth()->id(),
                ]);

                $remainingQty -= $deductQty;
            }

            if ($remainingQty > 0) {
                throw new \Exception("Insufficient stock. Requested: {$quantity}, Available: " . ($quantity - $remainingQty));
            }

            return true;
        });
    }

    /**
     * Get current stock level
     *
     * @param int $productId
     * @param int $warehouseId
     * @return float
     */
    public function getCurrentStock(int $productId, int $warehouseId): float
    {
        return $this->repository->getProductStock($productId, $warehouseId);
    }

    /**
     * Get stock movements
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return Collection
     */
    public function getStockMovements(int $productId, ?int $warehouseId = null): Collection
    {
        return $this->repository->getStockMovements($productId, $warehouseId);
    }
}
