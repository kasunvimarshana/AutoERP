<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->getKey(),
            'code' => (string) $this->resource->code,
            'name' => (string) $this->resource->name,
            'phone_code' => $this->resource->phone_code,
            'is_active' => (bool) $this->resource->is_active,
            'row_version' => (int) $this->resource->row_version,
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
