<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CycleCountLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->resource->id,
            'tenant_id'      => $this->resource->tenantId,
            'cycle_count_id' => $this->resource->cycleCountId,
            'product_id'     => $this->resource->productId,
            'variant_id'     => $this->resource->variantId,
            'system_qty'     => $this->resource->systemQty,
            'counted_qty'    => $this->resource->countedQty,
            'variance'       => $this->resource->variance,
            'batch_number'   => $this->resource->batchNumber,
            'lot_number'     => $this->resource->lotNumber,
            'serial_number'  => $this->resource->serialNumber,
            'created_at'     => $this->resource->createdAt,
            'updated_at'     => $this->resource->updatedAt,
        ];
    }
}
