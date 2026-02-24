<?php

namespace Modules\Inventory\Application\Listeners;

use Modules\ECommerce\Domain\Events\ECommerceOrderConfirmed;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;


class HandleECommerceOrderConfirmedListener
{
    public function __construct(
        private StockLevelService $stockLevelService,
        private StockMovementRepositoryInterface $movementRepo,
    ) {}

    public function handle(ECommerceOrderConfirmed $event): void
    {
        if ($event->tenantId === '' || empty($event->lines)) {
            return;
        }

        foreach ($event->lines as $line) {
            $productId  = $line['inventory_product_id'] ?? null;
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
                    'type'             => 'sale',
                    'product_id'       => $productId,
                    'from_location_id' => $locationId,
                    'qty'              => $qty,
                    'reference_type'   => 'ecommerce_order',
                    'reference_id'     => $event->orderId,
                    'posted_at'        => now(),
                ]);
            } catch (\Throwable) {
                // Graceful degradation: stock tracking failure must not abort
                // the e-commerce order confirmation.
            }
        }
    }
}
