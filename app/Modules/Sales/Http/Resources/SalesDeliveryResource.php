<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesDeliveryResource extends ModuleResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'delivery_number' => $this->delivery_number,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
            'sales_order' => $this->whenLoaded('salesOrder', fn () => $this->summary($this->salesOrder, ['sales_order_number', 'status'])),
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'notes' => $this->notes,
            'posted_at' => $this->posted_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'sales_order_line_id' => $line->sales_order_line_id,
                'item' => $line->relationLoaded('item') ? $this->summary($line->item, ['code', 'name', 'sku']) : null,
                'item_variant' => $line->relationLoaded('variant') ? $this->summary($line->variant, ['code', 'name', 'sku']) : null,
                'uom' => $line->relationLoaded('uom') ? $this->summary($line->uom, ['code', 'name', 'symbol']) : null,
                'ordered_quantity' => (string) $line->ordered_quantity,
                'delivered_quantity' => (string) $line->delivered_quantity,
                'invoiced_quantity' => (string) $line->invoiced_quantity,
                'returned_quantity' => (string) $line->returned_quantity,
                'remaining_quantity' => (string) $line->remaining_quantity,
                'unit_price' => (string) $line->unit_price,
                'line_total' => (string) $line->line_total,
                'inventory_movement_id' => $line->inventory_movement_id,
                'status' => $line->status,
            ])->all(), []),
            'adjustments' => $this->whenLoaded('adjustments', fn () => SalesHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
