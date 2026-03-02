<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wms\Domain\Entities\Bin;

class BinResource extends JsonResource
{
    /** @var Bin */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'aisle_id' => $this->resource->aisleId,
            'code' => $this->resource->code,
            'description' => $this->resource->description,
            'max_capacity' => $this->resource->maxCapacity,
            'current_capacity' => $this->resource->currentCapacity,
            'is_active' => $this->resource->isActive,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
