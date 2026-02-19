<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PriceCalculationResource
 *
 * API resource for price calculation result transformation
 */
class PriceCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->resource['product_id'],
            'product' => [
                'id' => $this->resource['product']['id'],
                'name' => $this->resource['product']['name'],
                'sku' => $this->resource['product']['sku'],
            ],
            'quantity' => $this->resource['quantity'],
            'location_id' => $this->resource['location_id'] ?? null,
            'location' => $this->when(isset($this->resource['location']), [
                'id' => $this->resource['location']['id'] ?? null,
                'name' => $this->resource['location']['name'] ?? null,
            ]),
            'strategy' => [
                'value' => $this->resource['strategy']['value'],
                'label' => $this->resource['strategy']['label'],
            ],
            'calculation' => [
                'base_price' => $this->resource['calculation']['base_price'],
                'unit_price' => $this->resource['calculation']['unit_price'],
                'total_price' => $this->resource['calculation']['total_price'],
                'breakdown' => $this->resource['calculation']['breakdown'] ?? [],
            ],
            'date' => $this->resource['date'] ?? null,
            'calculated_at' => $this->resource['calculated_at'],
        ];
    }
}
