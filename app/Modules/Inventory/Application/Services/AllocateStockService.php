<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\AllocateStockServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class AllocateStockService implements AllocateStockServiceInterface
{
    public function __construct(
        private readonly InventoryLevelRepositoryInterface $levelRepo,
        private readonly InventoryBatchRepositoryInterface $batchRepo,
    ) {}

    public function execute(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        string $strategy = 'fifo',
    ): array {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Allocation quantity must be positive.");
        }

        $level = $this->levelRepo->findByProduct($tenantId, $productId, $warehouseId);
        if (!$level || $level->getAvailableQuantity() < $quantity - InventoryLevel::FLOAT_TOLERANCE) {
            $available = $level ? $level->getAvailableQuantity() : 0.0;
            throw new \DomainException(
                "Insufficient available stock. Available: {$available}, Requested: {$quantity}."
            );
        }

        $batches   = $this->batchRepo->findActiveBatches($tenantId, $productId, $warehouseId, $strategy);
        $remaining = $quantity;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remaining <= InventoryLevel::FLOAT_TOLERANCE) break;

            $allocQty = min($remaining, $batch->getQuantityRemaining());
            $allocations[] = [
                'batch_id'   => $batch->getId(),
                'quantity'   => $allocQty,
                'expires_at' => $batch->getExpiresAt()?->format('Y-m-d'),
            ];
            $remaining -= $allocQty;
        }

        // If no batches (non-batch-tracked product), allocate from level directly
        if (empty($allocations) && $remaining > InventoryLevel::FLOAT_TOLERANCE) {
            $allocations[] = [
                'batch_id'   => null,
                'quantity'   => $quantity,
                'expires_at' => null,
            ];
            $remaining = 0.0;
        }

        if ($remaining > InventoryLevel::FLOAT_TOLERANCE) {
            throw new \DomainException(
                "Could not fully allocate {$quantity} units from available batches. Short by {$remaining}."
            );
        }

        return $allocations;
    }
}
