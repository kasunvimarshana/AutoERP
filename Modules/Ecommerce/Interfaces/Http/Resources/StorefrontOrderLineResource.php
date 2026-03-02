<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Ecommerce\Domain\Entities\StorefrontOrderLine;

class StorefrontOrderLineResource extends JsonResource
{
    /** @var StorefrontOrderLine */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'order_id' => $this->resource->orderId,
            'product_id' => $this->resource->productId,
            'product_name' => $this->resource->productName,
            'sku' => $this->resource->sku,
            'quantity' => $this->resource->quantity,
            'unit_price' => $this->resource->unitPrice,
            'line_total' => $this->resource->lineTotal,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
