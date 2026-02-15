<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\StockLedgerRepositoryInterface;
use App\Modules\Product\Repositories\ProductRepositoryInterface;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryService extends BaseService
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * InventoryService constructor.
     *
     * @param StockLedgerRepositoryInterface $repository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        StockLedgerRepositoryInterface $repository,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($repository);
        $this->productRepository = $productRepository;
    }

    /**
     * Record stock IN transaction
     *
     * @param array $data
     * @return mixed
     */
    public function stockIn(array $data)
    {
        return $this->recordStockMovement(array_merge($data, [
            'quantity' => abs($data['quantity']),
        ]));
    }

    /**
     * Record stock OUT transaction
     *
     * @param array $data
     * @return mixed
     */
    public function stockOut(array $data)
    {
        // Verify sufficient stock before allowing stock out
        $currentBalance = $this->repository->getCurrentBalance(
            $data['product_id'],
            $data['branch_id'] ?? null,
            $data['location_id'] ?? null,
            $data['variant_id'] ?? null
        );

        $requestedQuantity = abs($data['quantity']);

        if ($currentBalance < $requestedQuantity) {
            throw new ServiceException(
                "Insufficient stock. Current balance: {$currentBalance}, Requested: {$requestedQuantity}"
            );
        }

        return $this->recordStockMovement(array_merge($data, [
            'quantity' => -1 * abs($data['quantity']),
        ]));
    }

    /**
     * Record stock adjustment
     *
     * @param array $data
     * @return mixed
     */
    public function stockAdjustment(array $data)
    {
        $currentBalance = $this->repository->getCurrentBalance(
            $data['product_id'],
            $data['branch_id'] ?? null,
            $data['location_id'] ?? null,
            $data['variant_id'] ?? null
        );

        $targetBalance = $data['target_balance'];
        $adjustmentQuantity = $targetBalance - $currentBalance;

        $transactionType = $adjustmentQuantity > 0 ? 'adjustment_increase' : 'adjustment_decrease';

        return $this->recordStockMovement(array_merge($data, [
            'quantity' => $adjustmentQuantity,
            'transaction_type' => $transactionType,
        ]));
    }

    /**
     * Record stock transfer between locations
     *
     * @param array $data
     * @return array
     */
    public function stockTransfer(array $data)
    {
        DB::beginTransaction();

        try {
            // Validate transfer data
            if (!isset($data['from_location_id']) || !isset($data['to_location_id'])) {
                throw new ServiceException('Both from_location_id and to_location_id are required');
            }

            if ($data['from_location_id'] === $data['to_location_id']) {
                throw new ServiceException('Cannot transfer to the same location');
            }

            // Record stock out from source location
            $stockOut = $this->stockOut([
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'branch_id' => $data['from_branch_id'] ?? null,
                'location_id' => $data['from_location_id'],
                'transaction_type' => 'transfer_out',
                'quantity' => $data['quantity'],
                'reference_type' => $data['reference_type'] ?? 'transfer',
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
                'notes' => $data['notes'] ?? 'Stock transfer out',
            ]);

            // Record stock in to destination location
            $stockIn = $this->stockIn([
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'branch_id' => $data['to_branch_id'] ?? $data['from_branch_id'] ?? null,
                'location_id' => $data['to_location_id'],
                'transaction_type' => 'transfer_in',
                'quantity' => $data['quantity'],
                'reference_type' => $data['reference_type'] ?? 'transfer',
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
                'notes' => $data['notes'] ?? 'Stock transfer in',
            ]);

            DB::commit();

            return [
                'success' => true,
                'stock_out' => $stockOut,
                'stock_in' => $stockIn,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to transfer stock: ' . $e->getMessage());
        }
    }

    /**
     * Get current stock balance
     *
     * @param int $productId
     * @param int|null $branchId
     * @param int|null $locationId
     * @param int|null $variantId
     * @return float
     */
    public function getCurrentBalance(
        int $productId,
        ?int $branchId = null,
        ?int $locationId = null,
        ?int $variantId = null
    ): float {
        return $this->repository->getCurrentBalance($productId, $branchId, $locationId, $variantId);
    }

    /**
     * Get stock movements
     *
     * @param int $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int|null $branchId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockMovements(
        int $productId,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $branchId = null
    ) {
        return $this->repository->getMovements($productId, $startDate, $endDate, $branchId);
    }

    /**
     * Get expiring items
     *
     * @param int $daysThreshold
     * @param int|null $branchId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringItems(int $daysThreshold = 30, ?int $branchId = null)
    {
        return $this->repository->getExpiringItems($daysThreshold, $branchId);
    }

    /**
     * Calculate stock valuation using selected method
     *
     * @param int $productId
     * @param string $method (fifo, lifo, average)
     * @param int|null $branchId
     * @return array
     */
    public function calculateStockValuation(
        int $productId,
        string $method = 'fifo',
        ?int $branchId = null
    ): array {
        $currentBalance = $this->getCurrentBalance($productId, $branchId);

        if ($currentBalance <= 0) {
            return [
                'quantity' => 0,
                'total_value' => 0,
                'average_cost' => 0,
                'method' => $method,
            ];
        }

        switch ($method) {
            case 'fifo':
                return $this->calculateFIFOValuation($productId, $currentBalance, $branchId);
            case 'lifo':
                return $this->calculateLIFOValuation($productId, $currentBalance, $branchId);
            case 'average':
            default:
                $averageCost = $this->repository->calculateAverageCost($productId, $branchId);
                return [
                    'quantity' => $currentBalance,
                    'total_value' => $currentBalance * $averageCost,
                    'average_cost' => $averageCost,
                    'method' => 'average',
                ];
        }
    }

    /**
     * Calculate FIFO valuation
     *
     * @param int $productId
     * @param float $quantity
     * @param int|null $branchId
     * @return array
     */
    protected function calculateFIFOValuation(int $productId, float $quantity, ?int $branchId = null): array
    {
        $batches = $this->repository->getFIFOBatches($productId, $branchId);
        $remainingQuantity = $quantity;
        $totalValue = 0;
        $totalQuantity = 0;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $batchQuantity = min($batch->running_balance, $remainingQuantity);
            $totalValue += $batchQuantity * $batch->unit_cost;
            $totalQuantity += $batchQuantity;
            $remainingQuantity -= $batchQuantity;
        }

        $averageCost = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;

        return [
            'quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'average_cost' => $averageCost,
            'method' => 'fifo',
        ];
    }

    /**
     * Calculate LIFO valuation
     *
     * @param int $productId
     * @param float $quantity
     * @param int|null $branchId
     * @return array
     */
    protected function calculateLIFOValuation(int $productId, float $quantity, ?int $branchId = null): array
    {
        $batches = $this->repository->getFIFOBatches($productId, $branchId)->reverse();
        $remainingQuantity = $quantity;
        $totalValue = 0;
        $totalQuantity = 0;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $batchQuantity = min($batch->running_balance, $remainingQuantity);
            $totalValue += $batchQuantity * $batch->unit_cost;
            $totalQuantity += $batchQuantity;
            $remainingQuantity -= $batchQuantity;
        }

        $averageCost = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;

        return [
            'quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'average_cost' => $averageCost,
            'method' => 'lifo',
        ];
    }

    /**
     * Record stock movement (internal method)
     *
     * @param array $data
     * @return mixed
     */
    protected function recordStockMovement(array $data)
    {
        DB::beginTransaction();

        try {
            // Validate product exists
            $product = $this->productRepository->findById($data['product_id']);
            if (!$product) {
                throw new ServiceException('Product not found');
            }

            // Set created_by
            $data['created_by'] = Auth::id();

            // Calculate total cost if unit cost is provided
            if (isset($data['unit_cost'])) {
                $data['total_cost'] = abs($data['quantity']) * $data['unit_cost'];
            }

            // Record the movement
            $ledgerEntry = $this->repository->recordMovement($data);

            DB::commit();

            return $ledgerEntry;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to record stock movement: ' . $e->getMessage());
        }
    }
}
