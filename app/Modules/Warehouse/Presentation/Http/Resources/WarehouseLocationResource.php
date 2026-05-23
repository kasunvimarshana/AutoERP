<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'warehouse_id' => $this->warehouse_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'path' => $this->path,
            'depth' => $this->depth,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'is_pickable' => $this->is_pickable,
            'is_receivable' => $this->is_receivable,
            'capacity' => $this->capacity,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
