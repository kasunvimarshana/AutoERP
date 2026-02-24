<?php

namespace Modules\Inventory\Application\Listeners;

use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Manufacturing\Domain\Events\WorkOrderCompleted;


class HandleWorkOrderCompletedListener
{
    public function __construct(
        private StockLevelService $stockLevelService,
        private StockMovementRepositoryInterface $movementRepo,
    ) {}

    public function handle(WorkOrderCompleted $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        // 1. Deduct BOM components consumed during production
        foreach ($event->components as $component) {
            $productId  = $component['product_id'] ?? null;
            $locationId = $component['location_id'] ?? null;
            $qty        = (string) ($component['qty_consumed'] ?? '0');

            if ($productId === null || $locationId === null) {
                continue;
            }

            if (bccomp($qty, '0', 8) <= 0) {
                continue;
            }

            try {
                $this->stockLevelService->decrease(
                    $productId,
                    $locationId,
                    $qty,
                    $event->tenantId,
                );

                $this->movementRepo->create([
                    'tenant_id'        => $event->tenantId,
                    'type'             => 'consumption',
                    'product_id'       => $productId,
                    'from_location_id' => $locationId,
                    'qty'              => $qty,
                    'reference_type'   => 'work_order',
                    'reference_id'     => $event->workOrderId,
                    'posted_at'        => now(),
                ]);
            } catch (\Throwable) {
                // Graceful degradation: insufficient stock or missing stock level
                // should not abort the production completion.
            }
        }

        // 2. Receive finished goods into inventory
        if ($event->finishedProductId !== null && $event->finishedLocationId !== null) {
            $this->stockLevelService->increase(
                $event->finishedProductId,
                $event->finishedLocationId,
                $event->quantityProduced,
                $event->tenantId,
            );

            $this->movementRepo->create([
                'tenant_id'      => $event->tenantId,
                'type'           => 'production',
                'product_id'     => $event->finishedProductId,
                'to_location_id' => $event->finishedLocationId,
                'qty'            => $event->quantityProduced,
                'reference_type' => 'work_order',
                'reference_id'   => $event->workOrderId,
                'posted_at'      => now(),
            ]);
        }
    }
}
