<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesOrderLineResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_number' => (int) $this->line_number,
            'quotation_line_id' => $this->quotation_line_id,
            'item_id' => $this->item_id,
            'item' => $this->relationLoaded('item')
                ? $this->summary($this->item, ['code', 'name', 'sku', 'item_type', 'is_stockable'])
                : null,
            'item_variant' => $this->relationLoaded('variant')
                ? $this->summary($this->variant, ['code', 'name', 'sku'])
                : null,
            'description' => $this->description,
            'uom_id' => $this->ordered_uom_id,
            'uom' => $this->relationLoaded('orderedUom')
                ? $this->summary($this->orderedUom, ['code', 'name', 'symbol'])
                : null,
            'base_uom' => $this->relationLoaded('baseUom')
                ? $this->summary($this->baseUom, ['code', 'name', 'symbol'])
                : null,
            'uom_conversion_factor' => (string) $this->uom_conversion_factor,
            'ordered_quantity' => (string) $this->ordered_quantity,
            'base_quantity' => (string) $this->base_quantity,
            'allocated_quantity' => (string) $this->allocated_quantity,
            'delivered_quantity' => (string) $this->delivered_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'returned_quantity' => (string) $this->returned_quantity,
            'cancelled_quantity' => (string) $this->cancelled_quantity,
            'remaining_allocatable_quantity' => (string) $this->remaining_allocatable_quantity,
            'remaining_deliverable_quantity' => (string) $this->remaining_deliverable_quantity,
            'remaining_invoiceable_quantity' => (string) $this->remaining_invoiceable_quantity,
            'remaining_returnable_quantity' => (string) $this->remaining_returnable_quantity,
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
            'inventory_allocation_id' => $this->inventory_allocation_id,
            'status' => $this->enumValue($this->status),
        ];
    }
}
