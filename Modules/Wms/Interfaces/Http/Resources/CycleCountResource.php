<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wms\Domain\Entities\CycleCount;

class CycleCountResource extends JsonResource
{
    /** @var CycleCount */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'warehouse_id' => $this->resource->warehouseId,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'started_at' => $this->resource->startedAt,
            'completed_at' => $this->resource->completedAt,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
