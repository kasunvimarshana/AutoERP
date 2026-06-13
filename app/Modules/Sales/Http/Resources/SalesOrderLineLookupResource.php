<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesOrderLineLookupResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'sales_order_line_id' => (int) $this->getKey(),
            'item' => $this->relationLoaded('item')
                ? $this->summary($this->item, ['code', 'name'])
                : null,
            'uom' => $this->relationLoaded('orderedUom')
                ? $this->summary($this->orderedUom, ['code', 'name', 'symbol'])
                : null,
            'ordered_quantity' => (string) $this->ordered_quantity,
            'allocated_quantity' => (string) $this->allocated_quantity,
            'delivered_quantity' => (string) $this->delivered_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'remaining_allocatable_quantity' => (string) $this->remaining_allocatable_quantity,
            'remaining_deliverable_quantity' => (string) $this->remaining_deliverable_quantity,
            'remaining_invoiceable_quantity' => (string) $this->remaining_invoiceable_quantity,
            'unit_price' => (string) $this->unit_price,
        ];
    }
}
