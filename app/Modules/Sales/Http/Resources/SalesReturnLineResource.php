<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesReturnLineResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'source_line_type' => $this->source_line_type,
            'source_line_id' => $this->source_line_id,
            'item' => $this->relationLoaded('item')
                ? $this->summary($this->item, ['code', 'name', 'sku'])
                : null,
            'item_variant' => $this->relationLoaded('variant')
                ? $this->summary($this->variant, ['code', 'name', 'sku'])
                : null,
            'uom' => $this->relationLoaded('uom')
                ? $this->summary($this->uom, ['code', 'name', 'symbol'])
                : null,
            'returned_quantity' => (string) $this->returned_quantity,
            'source_quantity' => (string) $this->source_quantity,
            'previously_returned_quantity' => (string) $this->previously_returned_quantity,
            'remaining_quantity' => (string) $this->remaining_quantity,
            'unit_price' => (string) $this->unit_price,
            'discount_amount' => (string) $this->discount_amount,
            'tax_amount' => (string) $this->tax_amount,
            'charge_amount' => (string) $this->charge_amount,
            'line_total' => (string) $this->line_total,
            'condition_status' => $this->condition_status,
            'inventory_movement_id' => $this->inventory_movement_id,
            'reason' => $this->reason,
        ];
    }
}
