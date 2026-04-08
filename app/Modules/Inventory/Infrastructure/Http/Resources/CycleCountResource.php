<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CycleCountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->resource->id,
            'tenant_id'    => $this->resource->tenantId,
            'warehouse_id' => $this->resource->warehouseId,
            'location_id'  => $this->resource->locationId,
            'status'       => $this->resource->status,
            'scheduled_at' => $this->resource->scheduledAt,
            'completed_at' => $this->resource->completedAt,
            'notes'        => $this->resource->notes,
            'created_at'   => $this->resource->createdAt,
            'updated_at'   => $this->resource->updatedAt,
        ];
    }
}
