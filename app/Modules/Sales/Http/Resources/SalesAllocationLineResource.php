<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesAllocationLineResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'sales_order_line_id' => $this->sales_order_line_id,
            'line_number' => $this->line_number,
            'item' => $this->whenLoaded('item', fn () => $this->summary($this->item, ['sku', 'name', 'description'])),
            'variant' => $this->whenLoaded('variant', fn () => $this->summary($this->variant, ['sku', 'name'])),
            'uom' => $this->whenLoaded('uom', fn () => $this->summary($this->uom, ['code', 'name'])),
            'requested_quantity' => (string) $this->requested_quantity,
            'allocated_quantity' => (string) $this->allocated_quantity,
            'released_quantity' => (string) $this->released_quantity,
            'issued_quantity' => (string) $this->issued_quantity,
            'inventory_allocation_id' => $this->inventory_allocation_id,
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
        ];
    }
}
