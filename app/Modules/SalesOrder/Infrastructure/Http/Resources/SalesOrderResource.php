<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->getId(),
            'tenant_id'          => $this->getTenantId(),
            'reference_number'   => $this->getReferenceNumber(),
            'status'             => $this->getStatus(),
            'customer_id'        => $this->getCustomerId(),
            'customer_reference' => $this->getCustomerReference(),
            'order_date'         => $this->getOrderDate(),
            'required_date'      => $this->getRequiredDate(),
            'warehouse_id'       => $this->getWarehouseId(),
            'currency'           => $this->getCurrency(),
            'subtotal'           => $this->getSubtotal(),
            'tax_amount'         => $this->getTaxAmount(),
            'discount_amount'    => $this->getDiscountAmount(),
            'total_amount'       => $this->getTotalAmount(),
            'shipping_address'   => $this->getShippingAddress(),
            'notes'              => $this->getNotes(),
            'metadata'           => $this->getMetadata()->toArray(),
            'confirmed_by'       => $this->getConfirmedBy(),
            'confirmed_at'       => $this->getConfirmedAt()?->format('c'),
            'shipped_by'         => $this->getShippedBy(),
            'shipped_at'         => $this->getShippedAt()?->format('c'),
            'delivered_at'       => $this->getDeliveredAt()?->format('c'),
            'created_at'         => $this->getCreatedAt()->format('c'),
            'updated_at'         => $this->getUpdatedAt()->format('c'),
        ];
    }
}
