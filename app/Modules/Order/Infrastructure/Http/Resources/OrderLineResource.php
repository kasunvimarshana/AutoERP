<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->resource->id,
            'tenant_id'   => $this->resource->tenantId,
            'order_type'  => $this->resource->orderType,
            'order_id'    => $this->resource->orderId,
            'product_id'  => $this->resource->productId,
            'variant_id'  => $this->resource->variantId,
            'description' => $this->resource->description,
            'quantity'    => $this->resource->quantity,
            'unit_price'  => $this->resource->unitPrice,
            'discount'    => $this->resource->discount,
            'tax_rate'    => $this->resource->taxRate,
            'line_total'  => $this->resource->lineTotal,
            'created_at'  => $this->resource->createdAt,
            'updated_at'  => $this->resource->updatedAt,
        ];
    }
}
