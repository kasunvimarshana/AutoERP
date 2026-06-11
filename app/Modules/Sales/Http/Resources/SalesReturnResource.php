<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesReturnResource extends ModuleResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'return_number' => $this->return_number,
            'return_date' => $this->return_date?->toDateString(),
            'return_type' => $this->enumValue($this->return_type),
            'status' => $this->enumValue($this->status),
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'replacement_sales_order' => $this->whenLoaded('replacementSalesOrder', fn () => $this->summary($this->replacementSalesOrder, ['sales_order_number', 'status'])),
            'affects_inventory' => (bool) $this->affects_inventory,
            'affects_customer_balance' => (bool) $this->affects_customer_balance,
            'approval_required' => (bool) $this->approval_required,
            'reason' => $this->reason,
            'subtotal' => (string) $this->subtotal,
            'adjustment_return_total' => (string) $this->adjustment_return_total,
            'grand_total' => (string) $this->grand_total,
            'credit_note_id' => $this->credit_note_id,
            'credit_note' => $this->whenLoaded('creditNote', fn () => $this->summary($this->creditNote, ['credit_note_number', 'status', 'amount'])),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'source_line_type' => $line->source_line_type,
                'source_line_id' => $line->source_line_id,
                'item' => $line->relationLoaded('item') ? $this->summary($line->item, ['code', 'name', 'sku']) : null,
                'item_variant' => $line->relationLoaded('variant') ? $this->summary($line->variant, ['code', 'name', 'sku']) : null,
                'uom' => $line->relationLoaded('uom') ? $this->summary($line->uom, ['code', 'name', 'symbol']) : null,
                'returned_quantity' => (string) $line->returned_quantity,
                'source_quantity' => (string) $line->source_quantity,
                'previously_returned_quantity' => (string) $line->previously_returned_quantity,
                'remaining_quantity' => (string) $line->remaining_quantity,
                'unit_price' => (string) $line->unit_price,
                'discount_amount' => (string) $line->discount_amount,
                'tax_amount' => (string) $line->tax_amount,
                'charge_amount' => (string) $line->charge_amount,
                'line_total' => (string) $line->line_total,
                'condition_status' => $line->condition_status,
                'inventory_movement_id' => $line->inventory_movement_id,
                'reason' => $line->reason,
            ])->all(), []),
            'adjustment_allocations' => $this->whenLoaded('adjustmentAllocations', fn () => $this->adjustmentAllocations->map(fn ($allocation): array => [
                'id' => (int) $allocation->getKey(),
                'adjustment_type' => $this->enumValue($allocation->adjustment_type),
                'effect' => $this->enumValue($allocation->effect),
                'source_amount' => (string) $allocation->source_amount,
                'previously_returned_amount' => (string) $allocation->previously_returned_amount,
                'returned_amount' => (string) $allocation->returned_amount,
                'remaining_amount' => (string) $allocation->remaining_amount,
            ])->all(), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
