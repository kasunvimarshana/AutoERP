<?php

namespace Modules\Inventory\Application\Listeners;

use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Logistics\Domain\Events\DeliveryCompleted;


class HandleDeliveryCompletedListener
{
    public function __construct(
        private StockLevelService $stockLevelService,
        private StockMovementRepositoryInterface $movementRepo,
    ) {}

    public function handle(DeliveryCompleted $event): void
    {
        if ($event->tenantId === '' || empty($event->lines)) {
            return;
        }

        foreach ($event->lines as $line) {
            $productId  = $line['product_id'] ?? null;
            $locationId = $line['location_id'] ?? null;
            $qty        = (string) ($line['qty'] ?? '0');

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
                    'type'             => 'delivery',
                    'product_id'       => $productId,
                    'from_location_id' => $locationId,
                    'qty'              => $qty,
                    'reference_type'   => 'delivery_order',
                    'reference_id'     => $event->deliveryOrderId,
                    'posted_at'        => now(),
                ]);
            } catch (\Throwable) {
                // Graceful degradation: insufficient stock or missing stock level
                // should not prevent the delivery from being marked as complete.
            }
        }
    }
}
