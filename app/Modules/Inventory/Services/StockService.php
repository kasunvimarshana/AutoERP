<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Inventory\Repositories\StockRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService extends BaseService
{
    protected StockMovementRepository $stockMovementRepository;

    /**
     * StockService constructor
     */
    public function __construct(
        StockRepository $repository,
        StockMovementRepository $stockMovementRepository
    ) {
        $this->repository = $repository;
        $this->stockMovementRepository = $stockMovementRepository;
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        string $type,
        ?string $reason = null
    ): bool {
        DB::beginTransaction();

        try {
            $stock = $this->repository->getByProductAndWarehouse($productId, $warehouseId);

            if (! $stock) {
                $stock = $this->repository->create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                ]);
            }

            $oldQuantity = $stock->quantity;
            $newQuantity = $type === 'in' ? $oldQuantity + $quantity : $oldQuantity - $quantity;

            if ($newQuantity < 0) {
                throw new \Exception('Insufficient stock quantity');
            }

            $this->repository->update($stock->id, ['quantity' => $newQuantity]);

            $this->stockMovementRepository->create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'quantity' => $quantity,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            DB::commit();

            Log::info("Stock adjusted for product {$productId} in warehouse {$warehouseId}: {$type} {$quantity}");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adjusting stock: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?string $reason = null
    ): bool {
        DB::beginTransaction();

        try {
            $fromStock = $this->repository->getByProductAndWarehouse($productId, $fromWarehouseId);

            if (! $fromStock || $fromStock->quantity < $quantity) {
                throw new \Exception('Insufficient stock in source warehouse');
            }

            $this->adjustStock($productId, $fromWarehouseId, $quantity, 'out', "Transfer to warehouse {$toWarehouseId}: {$reason}");
            $this->adjustStock($productId, $toWarehouseId, $quantity, 'in', "Transfer from warehouse {$fromWarehouseId}: {$reason}");

            DB::commit();

            Log::info("Stock transferred for product {$productId} from warehouse {$fromWarehouseId} to {$toWarehouseId}: {$quantity}");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error transferring stock: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available quantity for a product
     */
    public function getAvailableQuantity(int $productId, ?int $warehouseId = null): int
    {
        try {
            if ($warehouseId) {
                $stock = $this->repository->getByProductAndWarehouse($productId, $warehouseId);

                return $stock ? $stock->quantity : 0;
            }

            return $this->repository->getTotalQuantityByProduct($productId);
        } catch (\Exception $e) {
            Log::error('Error fetching available quantity: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get low stock items
     */
    public function getLowStock()
    {
        try {
            return $this->repository->getLowStock();
        } catch (\Exception $e) {
            Log::error('Error fetching low stock items: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get stock by product
     */
    public function getByProduct(int $productId)
    {
        try {
            return $this->repository->getByProduct($productId);
        } catch (\Exception $e) {
            Log::error("Error fetching stock for product {$productId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get stock by warehouse
     */
    public function getByWarehouse(int $warehouseId)
    {
        try {
            return $this->repository->getByWarehouse($warehouseId);
        } catch (\Exception $e) {
            Log::error("Error fetching stock for warehouse {$warehouseId}: ".$e->getMessage());
            throw $e;
        }
    }
}
