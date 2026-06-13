<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesQuotationLineResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_number' => (int) $this->line_number,
            'item_id' => $this->item_id,
            'item' => $this->relationLoaded('item')
                ? $this->summary($this->item, ['code', 'name', 'sku'])
                : null,
            'item_variant' => $this->relationLoaded('variant')
                ? $this->summary($this->variant, ['code', 'name', 'sku'])
                : null,
            'description' => $this->description,
            'uom_id' => $this->uom_id,
            'uom' => $this->relationLoaded('uom')
                ? $this->summary($this->uom, ['code', 'name', 'symbol'])
                : null,
            'quantity' => (string) $this->quantity,
            'unit_price' => (string) $this->unit_price,
            'line_subtotal' => (string) $this->line_subtotal,
            'discount_calculation_type' => $this->enumValue($this->discount_calculation_type),
            'discount_rate' => (string) $this->discount_rate,
            'discount_amount' => (string) $this->discount_amount,
            'tax_calculation_type' => $this->enumValue($this->tax_calculation_type),
            'tax_rate' => (string) $this->tax_rate,
            'tax_amount' => (string) $this->tax_amount,
            'charge_calculation_type' => $this->enumValue($this->charge_calculation_type),
            'charge_rate' => (string) $this->charge_rate,
            'charge_amount' => (string) $this->charge_amount,
            'line_total' => (string) $this->line_total,
            'status' => $this->status,
        ];
    }
}
