<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockLevelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->resource->id,
            'tenant_id'          => $this->resource->tenantId,
            'product_id'         => $this->resource->productId,
            'variant_id'         => $this->resource->variantId,
            'warehouse_id'       => $this->resource->warehouseId,
            'location_id'        => $this->resource->locationId,
            'batch_number'       => $this->resource->batchNumber,
            'lot_number'         => $this->resource->lotNumber,
            'serial_number'      => $this->resource->serialNumber,
            'quantity'           => $this->resource->quantity,
            'reserved_quantity'  => $this->resource->reservedQuantity,
            'available_quantity' => $this->resource->availableQuantity,
            'expiry_date'        => $this->resource->expiryDate?->format('Y-m-d'),
            'is_expired'         => $this->resource->isExpired(),
            'created_at'         => $this->resource->createdAt,
            'updated_at'         => $this->resource->updatedAt,
        ];
    }
}
