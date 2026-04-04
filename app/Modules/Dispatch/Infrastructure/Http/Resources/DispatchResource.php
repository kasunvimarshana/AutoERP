<?php

namespace Modules\Dispatch\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatch\Domain\Entities\Dispatch;

class DispatchResource extends JsonResource
{
    public function __construct(private readonly Dispatch $dispatch)
    {
        parent::__construct($dispatch);
    }

    public function toArray($request): array
    {
        return [
            'id'               => $this->dispatch->id,
            'tenant_id'        => $this->dispatch->tenantId,
            'sales_order_id'   => $this->dispatch->salesOrderId,
            'warehouse_id'     => $this->dispatch->warehouseId,
            'dispatch_number'  => $this->dispatch->dispatchNumber,
            'status'           => $this->dispatch->status,
            'tracking_number'  => $this->dispatch->trackingNumber,
            'carrier'          => $this->dispatch->carrier,
            'shipping_address' => $this->dispatch->shippingAddress,
            'notes'            => $this->dispatch->notes,
            'dispatched_at'    => $this->dispatch->dispatchedAt?->format('Y-m-d\TH:i:s\Z'),
            'delivered_at'     => $this->dispatch->deliveredAt?->format('Y-m-d\TH:i:s\Z'),
            'dispatched_by'    => $this->dispatch->dispatchedBy,
        ];
    }
}
