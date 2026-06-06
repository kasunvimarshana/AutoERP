<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class GoodsReceiptNoteResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'grn_number' => $this->grn_number,
            'received_date' => $this->received_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
            'purchase_order' => $this->whenLoaded('purchaseOrder', fn () => $this->summary($this->purchaseOrder, ['purchase_order_number', 'status'])),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'subtotal' => (string) $this->subtotal,
            'discount_total' => (string) $this->discount_total,
            'tax_total' => (string) $this->tax_total,
            'charge_total' => (string) $this->charge_total,
            'grand_total' => (string) $this->grand_total,
            'notes' => $this->notes,
            'posted_at' => $this->posted_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'purchase_order_line_id' => $line->purchase_order_line_id,
                'item' => $line->relationLoaded('item') ? $this->summary($line->item, ['code', 'name', 'sku']) : null,
                'item_variant' => $line->relationLoaded('variant') ? $this->summary($line->variant, ['code', 'name', 'sku']) : null,
                'uom' => $line->relationLoaded('uom') ? $this->summary($line->uom, ['code', 'name', 'symbol']) : null,
                'received_quantity' => (string) $line->received_quantity,
                'accepted_quantity' => (string) $line->accepted_quantity,
                'rejected_quantity' => (string) $line->rejected_quantity,
                'invoiced_quantity' => (string) $line->invoiced_quantity,
                'returned_quantity' => (string) $line->returned_quantity,
                'remaining_quantity' => (string) $line->remaining_quantity,
                'unit_price' => (string) $line->unit_price,
                'line_subtotal' => (string) $line->line_subtotal,
                'line_total' => (string) $line->line_total,
                'status' => $this->enumValue($line->status),
            ])->all(), []),
            'adjustments' => $this->whenLoaded('adjustments', fn () => PurchaseHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function summary(mixed $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }

        $data = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            if ($model->{$field} ?? null) {
                $data[$field] = $this->enumValue($model->{$field});
            }
        }

        return $data;
    }
}
