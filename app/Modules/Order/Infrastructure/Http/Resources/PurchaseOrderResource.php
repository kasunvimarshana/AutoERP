<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->resource->id,
            'tenant_id'     => $this->resource->tenantId,
            'supplier_id'   => $this->resource->supplierId,
            'warehouse_id'  => $this->resource->warehouseId,
            'reference'     => $this->resource->reference,
            'status'        => $this->resource->status,
            'order_date'    => $this->resource->orderDate,
            'expected_date' => $this->resource->expectedDate,
            'notes'         => $this->resource->notes,
            'total_amount'  => $this->resource->totalAmount,
            'created_at'    => $this->resource->createdAt,
            'updated_at'    => $this->resource->updatedAt,
        ];
    }
}
