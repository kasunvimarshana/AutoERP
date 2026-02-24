<?php

namespace Modules\ECommerce\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderLineRepositoryInterface;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderRepositoryInterface;
use Modules\ECommerce\Domain\Contracts\ProductListingRepositoryInterface;
use Modules\ECommerce\Domain\Events\ECommerceOrderConfirmed;

class ConfirmECommerceOrderUseCase
{
    public function __construct(
        private ECommerceOrderRepositoryInterface     $orderRepo,
        private ECommerceOrderLineRepositoryInterface $lineRepo,
        private ProductListingRepositoryInterface     $listingRepo,
    ) {}

    public function execute(string $orderId): object
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->orderRepo->findById($orderId);

            if (! $order) {
                throw new DomainException('E-Commerce order not found.');
            }

            if ($order->status !== 'pending') {
                throw new DomainException('Only pending orders can be confirmed.');
            }

            $updated = $this->orderRepo->update($orderId, [
                'status' => 'confirmed',
            ]);

            // Build enriched lines: resolve inventory_product_id from each product listing
            $lines = [];
            foreach ($this->lineRepo->findByOrder($orderId) as $line) {
                $inventoryProductId = null;
                if (! empty($line->product_listing_id)) {
                    $listing = $this->listingRepo->findById($line->product_listing_id);
                    $inventoryProductId = $listing?->inventory_product_id ?? null;
                }

                $lines[] = [
                    'inventory_product_id' => $inventoryProductId,
                    'qty'                  => (string) $line->quantity,
                    'location_id'          => null, // source location not configured per line; listener will skip when null
                ];
            }

            Event::dispatch(new ECommerceOrderConfirmed($orderId, $order->tenant_id, $lines));

            return $updated;
        });
    }
}
