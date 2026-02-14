<?php

namespace App\Modules\Inventory\Repositories;

use App\Base\BaseRepository;
use App\Modules\Inventory\Models\BatchLot;
use Illuminate\Database\Eloquent\Collection;

class BatchLotRepository extends BaseRepository
{
    public function __construct(BatchLot $model)
    {
        parent::__construct($model);
    }

    public function findByBatch(string $batchNumber): ?BatchLot
    {
        return $this->model->where('batch_number', $batchNumber)->first();
    }

    public function getExpiringSoon(int $days = 30, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->whereDate('expiry_date', '>=', now())
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    public function getActiveByVariant(string $variantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('product_variant_id', $variantId)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->get();
    }

    public function allocateFIFO(string $variantId, int $quantity): array
    {
        $batches = $this->model
            ->where('product_variant_id', $variantId)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->allocateFromBatches($batches, $quantity);
    }

    public function allocateFEFO(string $variantId, int $quantity): array
    {
        $batches = $this->model
            ->where('product_variant_id', $variantId)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->allocateFromBatches($batches, $quantity);
    }

    protected function allocateFromBatches(Collection $batches, int $quantity): array
    {
        $allocations = [];
        $remainingQuantity = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $allocatedQuantity = min($batch->quantity_remaining, $remainingQuantity);

            $allocations[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'lot_number' => $batch->lot_number,
                'quantity' => $allocatedQuantity,
                'unit_cost' => $batch->unit_cost,
            ];

            $remainingQuantity -= $allocatedQuantity;
        }

        if ($remainingQuantity > 0) {
            throw new \Exception("Insufficient stock. Required: {$quantity}, Available: " . ($quantity - $remainingQuantity));
        }

        return $allocations;
    }

    public function getByBranch(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->get();
    }

    public function getByVariantAndBranch(string $variantId, string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('product_variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->get();
    }
}
