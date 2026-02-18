<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_code' => $this->code,
            'location_name' => $this->name,
            'location_type' => $this->type ?? 'standard',
            'warehouse_id' => $this->warehouse_id,
        ];
    }
}
