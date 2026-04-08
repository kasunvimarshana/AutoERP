<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxGroupRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->resource->id,
            'tenant_id'    => $this->resource->tenantId,
            'tax_group_id' => $this->resource->taxGroupId,
            'name'         => $this->resource->name,
            'rate'         => $this->resource->rate,
            'type'         => $this->resource->type,
            'sequence'     => $this->resource->sequence,
            'is_active'    => $this->resource->isActive,
            'created_at'   => $this->resource->createdAt,
            'updated_at'   => $this->resource->updatedAt,
        ];
    }
}
