<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'tenant_id'      => $this->resource->tenantId,
            'from_currency'  => $this->resource->fromCurrency,
            'to_currency'    => $this->resource->toCurrency,
            'rate'           => $this->resource->rate,
            'effective_date' => $this->resource->effectiveDate->format('Y-m-d'),
            'source'         => $this->resource->source,
            'created_at'     => $this->resource->createdAt,
            'updated_at'     => $this->resource->updatedAt,
        ];
    }
}
