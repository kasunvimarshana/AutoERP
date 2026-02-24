<?php

namespace Modules\Logistics\Application\UseCases;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Logistics\Domain\Contracts\DeliveryOrderRepositoryInterface;
use Modules\Logistics\Domain\Contracts\TrackingEventRepositoryInterface;

class AddTrackingEventUseCase
{
    public function __construct(
        private DeliveryOrderRepositoryInterface $orderRepo,
        private TrackingEventRepositoryInterface $trackingRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $orderId = $data['delivery_order_id'];
            $order   = $this->orderRepo->findById($orderId);

            if (! $order) {
                throw new ModelNotFoundException("Delivery order [{$orderId}] not found.");
            }

            $tenantId = auth()->user()?->tenant_id ?? $order->tenant_id;

            return $this->trackingRepo->create([
                'tenant_id'         => $tenantId,
                'delivery_order_id' => $orderId,
                'event_type'        => $data['event_type'],
                'location'          => $data['location'] ?? null,
                'description'       => $data['description'],
                'occurred_at'       => $data['occurred_at'],
            ]);
        });
    }
}
