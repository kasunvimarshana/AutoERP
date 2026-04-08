<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReturnLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->resource->id,
            'tenant_id'     => $this->resource->tenantId,
            'return_type'   => $this->resource->returnType,
            'return_id'     => $this->resource->returnId,
            'product_id'    => $this->resource->productId,
            'variant_id'    => $this->resource->variantId,
            'quantity'      => $this->resource->quantity,
            'unit_price'    => $this->resource->unitPrice,
            'line_total'    => $this->resource->lineTotal,
            'batch_number'  => $this->resource->batchNumber,
            'lot_number'    => $this->resource->lotNumber,
            'serial_number' => $this->resource->serialNumber,
            'condition'     => $this->resource->condition,
            'restockable'   => $this->resource->restockable,
            'quality_notes' => $this->resource->qualityNotes,
            'created_at'    => $this->resource->createdAt,
            'updated_at'    => $this->resource->updatedAt,
        ];
    }
}
