<?php

namespace Modules\Logistics\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Logistics\Domain\Contracts\DeliveryOrderRepositoryInterface;
use Modules\Logistics\Domain\Contracts\TrackingEventRepositoryInterface;
use Modules\Logistics\Domain\Events\DeliveryDispatched;

class DispatchDeliveryUseCase
{
    public function __construct(
        private DeliveryOrderRepositoryInterface $orderRepo,
        private TrackingEventRepositoryInterface $trackingRepo,
    ) {}

    public function execute(string $id): object
    {
        return DB::transaction(function () use ($id) {
            $order = $this->orderRepo->findById($id);

            if (! $order) {
                throw new ModelNotFoundException("Delivery order [{$id}] not found.");
            }

            if ($order->status !== 'pending') {
                throw new DomainException(
                    "Delivery order must be in 'pending' status to dispatch. Current status: '{$order->status}'."
                );
            }

            $tenantId = $order->tenant_id;

            $this->orderRepo->update($id, ['status' => 'dispatched']);

            $this->trackingRepo->create([
                'tenant_id'         => $tenantId,
                'delivery_order_id' => $id,
                'event_type'        => 'picked_up',
                'location'          => null,
                'description'       => 'Order dispatched and picked up by carrier.',
                'occurred_at'       => now(),
            ]);

            Event::dispatch(new DeliveryDispatched($id, $tenantId));

            return $this->orderRepo->findById($id);
        });
    }
}
