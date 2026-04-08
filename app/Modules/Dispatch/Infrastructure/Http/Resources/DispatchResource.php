<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DispatchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->getId(),
            'tenant_id'              => $this->getTenantId(),
            'reference_number'       => $this->getReferenceNumber(),
            'status'                 => $this->getStatus(),
            'warehouse_id'           => $this->getWarehouseId(),
            'sales_order_id'         => $this->getSalesOrderId(),
            'customer_id'            => $this->getCustomerId(),
            'customer_reference'     => $this->getCustomerReference(),
            'dispatch_date'          => $this->getDispatchDate(),
            'estimated_delivery_date'=> $this->getEstimatedDeliveryDate(),
            'actual_delivery_date'   => $this->getActualDeliveryDate(),
            'carrier'                => $this->getCarrier(),
            'tracking_number'        => $this->getTrackingNumber(),
            'currency'               => $this->getCurrency(),
            'total_weight'           => $this->getTotalWeight(),
            'notes'                  => $this->getNotes(),
            'metadata'               => $this->getMetadata()->toArray(),
            'confirmed_by'           => $this->getConfirmedBy(),
            'confirmed_at'           => $this->getConfirmedAt()?->format('c'),
            'shipped_by'             => $this->getShippedBy(),
            'shipped_at'             => $this->getShippedAt()?->format('c'),
            'created_at'             => $this->getCreatedAt()->format('c'),
            'updated_at'             => $this->getUpdatedAt()->format('c'),
        ];
    }
}
