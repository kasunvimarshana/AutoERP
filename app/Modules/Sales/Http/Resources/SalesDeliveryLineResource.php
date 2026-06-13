<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesDeliveryLineResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'sales_order_line_id' => $this->sales_order_line_id,
            'item' => $this->relationLoaded('item')
                ? $this->summary($this->item, ['code', 'name', 'sku'])
                : null,
            'item_variant' => $this->relationLoaded('variant')
                ? $this->summary($this->variant, ['code', 'name', 'sku'])
                : null,
            'uom' => $this->relationLoaded('uom')
                ? $this->summary($this->uom, ['code', 'name', 'symbol'])
                : null,
            'ordered_quantity' => (string) $this->ordered_quantity,
            'delivered_quantity' => (string) $this->delivered_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'returned_quantity' => (string) $this->returned_quantity,
            'remaining_quantity' => (string) $this->remaining_quantity,
            'unit_price' => (string) $this->unit_price,
            'line_total' => (string) $this->line_total,
            'inventory_movement_id' => $this->inventory_movement_id,
            'status' => $this->status,
        ];
    }
}
