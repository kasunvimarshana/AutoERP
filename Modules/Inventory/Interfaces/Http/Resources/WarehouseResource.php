<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\Warehouse;

class WarehouseResource extends JsonResource
{
    /** @var Warehouse */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'address' => $this->resource->address,
            'status' => $this->resource->status,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
