<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->resource->id,
            'tenant_id'      => $this->resource->tenantId,
            'product_id'     => $this->resource->productId,
            'variant_id'     => $this->resource->variantId,
            'warehouse_id'   => $this->resource->warehouseId,
            'location_id'    => $this->resource->locationId,
            'type'           => $this->resource->type,
            'quantity'       => $this->resource->quantity,
            'batch_number'   => $this->resource->batchNumber,
            'lot_number'     => $this->resource->lotNumber,
            'serial_number'  => $this->resource->serialNumber,
            'reference_type' => $this->resource->referenceType,
            'reference_id'   => $this->resource->referenceId,
            'notes'          => $this->resource->notes,
            'created_at'     => $this->resource->createdAt,
            'updated_at'     => $this->resource->updatedAt,
        ];
    }
}
