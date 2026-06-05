<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UomLookupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'uom_code' => $this->resource->uom_code,
            'name' => $this->resource->name,
            'symbol' => $this->resource->symbol,
        ];
    }
}
