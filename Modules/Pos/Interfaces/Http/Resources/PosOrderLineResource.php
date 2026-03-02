<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pos\Domain\Entities\PosOrderLine;

class PosOrderLineResource extends JsonResource
{
    /** @var PosOrderLine */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'pos_order_id' => $this->resource->posOrderId,
            'product_id' => $this->resource->productId,
            'product_name' => $this->resource->productName,
            'sku' => $this->resource->sku,
            'quantity' => $this->resource->quantity,
            'unit_price' => $this->resource->unitPrice,
            'discount_amount' => $this->resource->discountAmount,
            'tax_amount' => $this->resource->taxAmount,
            'line_total' => $this->resource->lineTotal,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
