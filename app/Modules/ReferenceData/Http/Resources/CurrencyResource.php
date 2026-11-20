<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultCode = strtoupper(trim((string) config('app.bootstrap.currency_code', 'USD')));

        return [
            'id' => (int) $this->resource->getKey(),
            'code' => (string) $this->resource->code,
            'name' => (string) $this->resource->name,
            'symbol' => $this->resource->symbol,
            'decimal_places' => (int) $this->resource->decimal_places,
            'is_active' => (bool) $this->resource->is_active,
            'is_default' => strtoupper(trim((string) $this->resource->code)) === $defaultCode,
            'row_version' => (int) $this->resource->row_version,
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
