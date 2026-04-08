<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->resource->id,
            'tenant_id'  => $this->resource->tenantId,
            'name'       => $this->resource->name,
            'currency'   => $this->resource->currency,
            'is_default' => $this->resource->isDefault,
            'is_active'  => $this->resource->isActive,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
