<?php

namespace Modules\Inventory\Application\Listeners;

use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\POS\Domain\Events\PosOrderPlaced;


class HandlePosOrderPlacedListener
{
    public function __construct(
        private StockLevelService $stockLevelService,
        private StockMovementRepositoryInterface $movementRepo,
    ) {}

    public function handle(PosOrderPlaced $event): void
    {
        if ($event->tenantId === '' || empty($event->lines)) {
            return;
        }

        foreach ($event->lines as $line) {
            $productId  = $line['product_id'] ?? null;
            $locationId = $line['location_id'] ?? null;
            $qty        = (string) ($line['quantity'] ?? '0');

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
                    $line['variant_id'] ?? null,
                );

                $this->movementRepo->create([
                    'tenant_id'        => $event->tenantId,
                    'type'             => 'delivery',
                    'product_id'       => $productId,
                    'variant_id'       => $line['variant_id'] ?? null,
                    'from_location_id' => $locationId,
                    'qty'              => $qty,
                    'reference_type'   => 'pos_order',
                    'reference_id'     => $event->orderId,
                    'posted_at'        => now(),
                ]);
            } catch (\Throwable) {
                // Graceful degradation: insufficient-stock or missing stock level
                // should not prevent the POS order from being recorded.
            }
        }
    }
}
