<?php

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'tenant_id'  => $this->tenant_id,
            'name'       => $this->name,
            'code'       => $this->code,
            'type'       => $this->type,
            'address'    => $this->address ?? [],
            'contact'    => $this->contact ?? [],
            'capacity'   => $this->capacity ? (float) $this->capacity : null,
            'is_active'  => (bool) $this->is_active,
            'metadata'   => $this->metadata ?? [],
            'stock_summary' => $this->when(isset($this->stock_summary), fn () => $this->stock_summary),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
