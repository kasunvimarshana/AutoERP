<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class WarehouseLocationResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'warehouse_id'  => $this->warehouse_id,
            'parent_id'     => $this->parent_id,
            'code'          => $this->code,
            'name'          => $this->name,
            'type'          => $this->type,
            'barcode'       => $this->barcode,
            'is_active'     => $this->is_active,
            'is_pickable'   => $this->is_pickable,
            'is_receivable' => $this->is_receivable,
            'sort_order'    => $this->sort_order,
            'children'      => WarehouseLocationResource::collection($this->whenLoaded('children')),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
