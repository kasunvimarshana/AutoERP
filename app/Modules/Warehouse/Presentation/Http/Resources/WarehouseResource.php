<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'name' => $this->name,
            'code' => $this->code,
            'image_path' => $this->image_path,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
