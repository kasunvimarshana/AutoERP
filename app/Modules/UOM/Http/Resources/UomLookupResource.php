<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class UomLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource instanceof DataRecord
            ? $this->resource->toArray()
            : (is_array($this->resource) ? $this->resource : []);

        return [
            'id' => $data['id'] ?? null,
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'symbol' => $data['symbol'] ?? null,
            'type' => $data['type'] ?? null,
            'category' => $data['category'] ?? null,
            'decimal_precision' => $data['decimal_precision'] ?? 0,
            'allow_fractional_quantity' => $data['allow_fractional_quantity'] ?? false,
            'is_base' => $data['is_base'] ?? false,
        ];
    }
}
