<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->resource->id,
            'tenant_id'   => $this->resource->tenantId,
            'name'        => $this->resource->name,
            'code'        => $this->resource->code,
            'description' => $this->resource->description,
            'is_compound' => $this->resource->isCompound,
            'is_active'   => $this->resource->isActive,
            'created_at'  => $this->resource->createdAt,
            'updated_at'  => $this->resource->updatedAt,
        ];
    }
}
