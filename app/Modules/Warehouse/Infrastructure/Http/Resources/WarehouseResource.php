<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'name'       => $this->name,
            'code'       => $this->code,
            'type'       => $this->type,
            'address'    => $this->address,
            'is_active'  => $this->is_active,
            'metadata'   => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
