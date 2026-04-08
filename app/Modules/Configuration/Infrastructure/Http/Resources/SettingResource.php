<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->resource->id,
            'tenant_id'   => $this->resource->tenantId,
            'key'         => $this->resource->key,
            'value'       => $this->resource->getValue(),
            'group'       => $this->resource->group,
            'type'        => $this->resource->type,
            'is_public'   => $this->resource->isPublic,
            'description' => $this->resource->description,
            'created_at'  => $this->resource->createdAt,
            'updated_at'  => $this->resource->updatedAt,
        ];
    }
}
