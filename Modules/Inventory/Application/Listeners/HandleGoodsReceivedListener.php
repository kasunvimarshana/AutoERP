<?php

namespace Modules\Inventory\Application\Listeners;

use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Purchase\Domain\Events\GoodsReceived;


class HandleGoodsReceivedListener
{
    public function __construct(
        private StockLevelService $stockLevelService,
        private StockMovementRepositoryInterface $movementRepo,
    ) {}

    public function handle(GoodsReceived $event): void
    {
        if ($event->tenantId === '' || empty($event->lines)) {
            return;
        }

        foreach ($event->lines as $line) {
            $productId   = $line['product_id'] ?? null;
            $locationId  = $line['location_id'] ?? null;
            $qtyAccepted = (string) ($line['qty_accepted'] ?? '0');

            if ($productId === null || $locationId === null) {
                continue;
            }

            if (bccomp($qtyAccepted, '0', 8) <= 0) {
                continue;
            }

            $this->stockLevelService->increase(
                $productId,
                $locationId,
                $qtyAccepted,
                $event->tenantId,
                $line['variant_id'] ?? null,
            );

            $this->movementRepo->create([
                'tenant_id'      => $event->tenantId,
                'type'           => 'receipt',
                'product_id'     => $productId,
                'variant_id'     => $line['variant_id'] ?? null,
                'to_location_id' => $locationId,
                'qty'            => $qtyAccepted,
                'reference_type' => 'purchase_grn',
                'reference_id'   => $event->grnId,
                'posted_at'      => now(),
            ]);
        }
    }
}
