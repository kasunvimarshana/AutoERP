<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UomListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_unit_id' => $this->resource->organization_unit_id,
            'uom_code' => $this->resource->uom_code,
            'name' => $this->resource->name,
            'symbol' => $this->resource->symbol,
            'decimal_precision' => $this->resource->decimal_precision,
            'is_base' => $this->resource->is_base,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
