<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesOrderResource extends ModuleResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'sales_order_number' => $this->sales_order_number,
            'sales_order_date' => $this->sales_order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name', 'payment_term_id', 'default_currency_id'])),
            'quotation' => $this->whenLoaded('quotation', fn () => $this->summary($this->quotation, ['quotation_number', 'quotation_date', 'status'])),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location_id' => $this->warehouse_location_id,
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'exchange_rate' => (string) $this->exchange_rate,
            'subtotal' => (string) $this->subtotal,
            'line_discount_total' => (string) $this->line_discount_total,
            'line_tax_total' => (string) $this->line_tax_total,
            'line_charge_total' => (string) $this->line_charge_total,
            'header_increase_total' => (string) $this->header_increase_total,
            'header_decrease_total' => (string) $this->header_decrease_total,
            'grand_total' => (string) $this->grand_total,
            'allocated_total' => (string) $this->allocated_total,
            'delivered_total' => (string) $this->delivered_total,
            'invoiced_total' => (string) $this->invoiced_total,
            'returned_total' => (string) $this->returned_total,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'line_number' => (int) $line->line_number,
                'quotation_line_id' => $line->quotation_line_id,
                'item_id' => $line->item_id,
                'item' => $line->relationLoaded('item') ? $this->summary($line->item, ['code', 'name', 'sku', 'item_type', 'is_stockable']) : null,
                'item_variant' => $line->relationLoaded('variant') ? $this->summary($line->variant, ['code', 'name', 'sku']) : null,
                'description' => $line->description,
                'uom_id' => $line->ordered_uom_id,
                'uom' => $line->relationLoaded('orderedUom') ? $this->summary($line->orderedUom, ['code', 'name', 'symbol']) : null,
                'base_uom' => $line->relationLoaded('baseUom') ? $this->summary($line->baseUom, ['code', 'name', 'symbol']) : null,
                'uom_conversion_factor' => (string) $line->uom_conversion_factor,
                'ordered_quantity' => (string) $line->ordered_quantity,
                'base_quantity' => (string) $line->base_quantity,
                'allocated_quantity' => (string) $line->allocated_quantity,
                'delivered_quantity' => (string) $line->delivered_quantity,
                'invoiced_quantity' => (string) $line->invoiced_quantity,
                'returned_quantity' => (string) $line->returned_quantity,
                'cancelled_quantity' => (string) $line->cancelled_quantity,
                'remaining_allocatable_quantity' => (string) $line->remaining_allocatable_quantity,
                'remaining_deliverable_quantity' => (string) $line->remaining_deliverable_quantity,
                'remaining_invoiceable_quantity' => (string) $line->remaining_invoiceable_quantity,
                'remaining_returnable_quantity' => (string) $line->remaining_returnable_quantity,
                'unit_price' => (string) $line->unit_price,
                'line_subtotal' => (string) $line->line_subtotal,
                'discount_calculation_type' => $line->discount_calculation_type,
                'discount_rate' => (string) $line->discount_rate,
                'discount_amount' => (string) $line->discount_amount,
                'tax_calculation_type' => $line->tax_calculation_type,
                'tax_rate' => (string) $line->tax_rate,
                'tax_amount' => (string) $line->tax_amount,
                'charge_calculation_type' => $line->charge_calculation_type,
                'charge_rate' => (string) $line->charge_rate,
                'charge_amount' => (string) $line->charge_amount,
                'line_total' => (string) $line->line_total,
                'inventory_allocation_id' => $line->inventory_allocation_id,
                'status' => $this->enumValue($line->status),
            ])->all(), []),
            'adjustments' => $this->whenLoaded('adjustments', fn () => SalesHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
