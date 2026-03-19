<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'tenant_id'    => $this->tenant_id,
            'key'          => $this->key,
            'value'        => $this->is_sensitive ? '***' : $this->typedValue(),
            'type'         => $this->type,
            'group'        => $this->group,
            'is_sensitive' => $this->is_sensitive,
            'description'  => $this->description,
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
