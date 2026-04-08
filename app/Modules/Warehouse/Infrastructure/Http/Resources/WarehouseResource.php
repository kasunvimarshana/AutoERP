<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class WarehouseResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'name'          => $this->name,
            'type'          => $this->type,
            'description'   => $this->description,
            'address_line1' => $this->address_line1,
            'city'          => $this->city,
            'country'       => $this->country,
            'contact_name'  => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'is_active'     => $this->is_active,
            'is_default'    => $this->is_default,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
