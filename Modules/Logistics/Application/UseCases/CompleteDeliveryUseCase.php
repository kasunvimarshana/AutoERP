<?php

namespace Modules\Logistics\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Logistics\Domain\Contracts\DeliveryLineRepositoryInterface;
use Modules\Logistics\Domain\Contracts\DeliveryOrderRepositoryInterface;
use Modules\Logistics\Domain\Contracts\TrackingEventRepositoryInterface;
use Modules\Logistics\Domain\Events\DeliveryCompleted;

class CompleteDeliveryUseCase
{
    private const COMPLETABLE_STATUSES = ['dispatched', 'in_transit'];

    public function __construct(
        private DeliveryOrderRepositoryInterface $orderRepo,
        private TrackingEventRepositoryInterface $trackingRepo,
        private ?DeliveryLineRepositoryInterface  $lineRepo = null,
    ) {}

    public function execute(string $id): object
    {
        return DB::transaction(function () use ($id) {
            $order = $this->orderRepo->findById($id);

            if (! $order) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "Delivery order [{$id}] not found."
                );
            }

            if (! in_array($order->status, self::COMPLETABLE_STATUSES, true)) {
                throw new DomainException(
                    "Delivery order must be in 'dispatched' or 'in_transit' status to complete. Current status: '{$order->status}'."
                );
            }

            $tenantId   = $order->tenant_id;
            $locationId = $order->source_location_id ?? null;

            $this->orderRepo->update($id, [
                'status'         => 'delivered',
                'delivered_date' => now()->toDateString(),
            ]);

            $this->trackingRepo->create([
                'tenant_id'         => $tenantId,
                'delivery_order_id' => $id,
                'event_type'        => 'delivered',
                'location'          => null,
                'description'       => 'Order successfully delivered.',
                'occurred_at'       => now(),
            ]);

            // Build lines array for the DeliveryCompleted event so that the
            // Inventory module can deduct stock for each delivered product.
            $lines = [];
            if ($this->lineRepo !== null && $locationId !== null) {
                foreach ($this->lineRepo->findByDeliveryOrder($id) as $line) {
                    $productId = $line->product_id ?? null;
                    $qty       = (string) ($line->quantity ?? '0');

                    if ($productId === null) {
                        continue;
                    }

                    if (bccomp($qty, '0', 8) <= 0) {
                        continue;
                    }

                    $lines[] = [
                        'product_id'  => $productId,
                        'qty'         => $qty,
                        'location_id' => $locationId,
                    ];
                }
            }

            Event::dispatch(new DeliveryCompleted($id, $tenantId, $lines));

            return $this->orderRepo->findById($id);
        });
    }
}
