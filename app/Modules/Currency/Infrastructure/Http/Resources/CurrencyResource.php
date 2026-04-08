<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'tenant_id'      => $this->resource->tenantId,
            'code'           => $this->resource->code,
            'name'           => $this->resource->name,
            'symbol'         => $this->resource->symbol,
            'decimal_places' => $this->resource->decimalPlaces,
            'is_base'        => $this->resource->isBase,
            'is_active'      => $this->resource->isActive,
            'created_at'     => $this->resource->createdAt,
            'updated_at'     => $this->resource->updatedAt,
        ];
    }
}
