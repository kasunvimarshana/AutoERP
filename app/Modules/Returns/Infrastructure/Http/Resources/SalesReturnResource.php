<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->resource->id,
            'tenant_id'          => $this->resource->tenantId,
            'sales_order_id'     => $this->resource->salesOrderId,
            'customer_id'        => $this->resource->customerId,
            'warehouse_id'       => $this->resource->warehouseId,
            'reference'          => $this->resource->reference,
            'status'             => $this->resource->status,
            'return_date'        => $this->resource->returnDate->format('Y-m-d'),
            'reason'             => $this->resource->reason,
            'total_amount'       => $this->resource->totalAmount,
            'credit_memo_number' => $this->resource->creditMemoNumber,
            'refund_amount'      => $this->resource->refundAmount,
            'restocking_fee'     => $this->resource->restockingFee,
            'notes'              => $this->resource->notes,
            'created_at'         => $this->resource->createdAt,
            'updated_at'         => $this->resource->updatedAt,
        ];
    }
}
