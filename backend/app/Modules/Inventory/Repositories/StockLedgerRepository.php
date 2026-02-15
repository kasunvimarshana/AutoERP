<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Database\Eloquent\Collection;

class StockLedgerRepository extends BaseRepository implements StockLedgerRepositoryInterface
{
    /**
     * StockLedgerRepository constructor.
     *
     * @param StockLedger $model
     */
    public function __construct(StockLedger $model)
    {
        parent::__construct($model);
    }

    /**
     * Record stock movement
     *
     * @param array $data
     * @return StockLedger
     */
    public function recordMovement(array $data): StockLedger
    {
        // Calculate running balance
        $currentBalance = $this->getCurrentBalance(
            $data['product_id'],
            $data['branch_id'] ?? null,
            $data['location_id'] ?? null,
            $data['variant_id'] ?? null
        );

        $quantity = $data['quantity'];
        $newBalance = $currentBalance + $quantity;

        $data['running_balance'] = $newBalance;

        return $this->create($data);
    }

    /**
     * Get current balance for a product
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
        return StockLedger::getCurrentBalance($productId, $branchId, $locationId, $variantId);
    }

    /**
     * Get stock movements for a product
     *
     * @param int $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int|null $branchId
     * @return Collection
     */
    public function getMovements(
        int $productId,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $branchId = null
    ): Collection {
        return StockLedger::getMovements($productId, $startDate, $endDate, $branchId);
    }

    /**
     * Get expiring items
     *
     * @param int $daysThreshold
     * @param int|null $branchId
     * @return Collection
     */
    public function getExpiringItems(int $daysThreshold = 30, ?int $branchId = null): Collection
    {
        return StockLedger::getExpiringItems($daysThreshold, $branchId);
    }

    /**
     * Get stock by batch
     *
     * @param string $batchNumber
     * @return Collection
     */
    public function getByBatch(string $batchNumber): Collection
    {
        return $this->model->where('batch_number', $batchNumber)->get();
    }

    /**
     * Get stock by serial
     *
     * @param string $serialNumber
     * @return Collection
     */
    public function getBySerial(string $serialNumber): Collection
    {
        return $this->model->where('serial_number', $serialNumber)->get();
    }

    /**
     * Calculate average cost
     *
     * @param int $productId
     * @param int|null $branchId
     * @return float
     */
    public function calculateAverageCost(int $productId, ?int $branchId = null): float
    {
        return StockLedger::calculateAverageCost($productId, $branchId);
    }

    /**
     * Get stock by location
     *
     * @param int $locationId
     * @return Collection
     */
    public function getByLocation(int $locationId): Collection
    {
        return $this->model
            ->where('location_id', $locationId)
            ->where('running_balance', '>', 0)
            ->get();
    }

    /**
     * Get FIFO batches for a product
     *
     * @param int $productId
     * @param int|null $branchId
     * @return Collection
     */
    public function getFIFOBatches(int $productId, ?int $branchId = null): Collection
    {
        $query = $this->model
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->where('running_balance', '>', 0)
            ->orderBy('created_at', 'asc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }
}
