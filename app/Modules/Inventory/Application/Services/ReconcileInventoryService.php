<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Events\InventoryAdjusted;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class ReconcileInventoryService implements ReconcileInventoryServiceInterface
{
    public function __construct(
        private readonly InventoryCycleCountRepositoryInterface $cycleRepo,
        private readonly InventoryLevelRepositoryInterface $levelRepo,
    ) {}

    public function execute(int $cycleCountId, int $completedBy, array $countedItems): InventoryCycleCount
    {
        return DB::transaction(function () use ($cycleCountId, $completedBy, $countedItems): InventoryCycleCount {
            $count = $this->cycleRepo->findById($cycleCountId);
            if (!$count) {
                throw new NotFoundException('InventoryCycleCount', $cycleCountId);
            }

            // Start the cycle count if it's still pending
            if ($count->getStatus() === 'pending') {
                $count->start($completedBy);
                $this->cycleRepo->update($cycleCountId, [
                    'status'     => 'in_progress',
                    'counted_by' => $completedBy,
                    'started_at' => now(),
                ]);
            }

            // Process each counted item: adjust inventory level to match physical count
            foreach ($countedItems as $item) {
                $productId   = (int)$item['product_id'];
                $warehouseId = (int)$item['warehouse_id'];
                $countedQty  = (float)$item['counted_qty'];

                $level = $this->levelRepo->upsert(
                    $count->getTenantId(), $productId, $warehouseId, null, 'fifo'
                );

                $diff = $level->adjust($countedQty);
                $this->levelRepo->update($level->getId(), [
                    'quantity_on_hand' => $level->getQuantityOnHand(),
                ]);

                if (abs($diff) > \Modules\Inventory\Domain\Entities\InventoryLevel::FLOAT_TOLERANCE) {
                    event(new InventoryAdjusted($count->getTenantId(), $productId, $warehouseId, $diff));
                }
            }

            // Complete the cycle count
            $count->complete();
            $this->cycleRepo->update($cycleCountId, [
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            return $this->cycleRepo->findById($cycleCountId);
        });
    }
}
