<?php

namespace Modules\SalesOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SalesOrder\Domain\Entities\SalesOrder;

class SalesOrderResource extends JsonResource
{
    public function __construct(private readonly SalesOrder $so)
    {
        parent::__construct($so);
    }

    public function toArray($request): array
    {
        return [
            'id'                     => $this->so->id,
            'tenant_id'              => $this->so->tenantId,
            'warehouse_id'           => $this->so->warehouseId,
            'customer_id'            => $this->so->customerId,
            'so_number'              => $this->so->soNumber,
            'status'                 => $this->so->status,
            'total_amount'           => $this->so->totalAmount,
            'tax_amount'             => $this->so->taxAmount,
            'discount_amount'        => $this->so->discountAmount,
            'currency'               => $this->so->currency,
            'shipping_address'       => $this->so->shippingAddress,
            'notes'                  => $this->so->notes,
            'expected_delivery_date' => $this->so->expectedDeliveryDate?->format('Y-m-d'),
        ];
    }
}
