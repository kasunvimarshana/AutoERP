<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseOrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_number' => (int) $this->line_number,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item', fn () => $this->summary($this->item, ['code', 'name', 'sku'])),
            'item_variant_id' => $this->item_variant_id,
            'item_variant' => $this->whenLoaded('variant', fn () => $this->summary($this->variant, ['code', 'name', 'sku'])),
            'description' => $this->description,
            'uom_id' => $this->uom_id,
            'uom' => $this->whenLoaded('uom', fn () => $this->summary($this->uom, ['code', 'name', 'symbol'])),
            'ordered_uom_id' => $this->ordered_uom_id,
            'base_uom_id' => $this->base_uom_id,
            'uom_conversion_factor' => (string) $this->uom_conversion_factor,
            'ordered_quantity' => (string) $this->ordered_quantity,
            'base_quantity' => (string) $this->base_quantity,
            'received_quantity' => (string) $this->received_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'returned_quantity' => (string) $this->returned_quantity,
            'cancelled_quantity' => (string) $this->cancelled_quantity,
            'remaining_quantity' => (string) $this->remaining_quantity,
            'remaining_receivable_quantity' => (string) $this->remaining_receivable_quantity,
            'remaining_invoiceable_quantity' => (string) $this->remaining_invoiceable_quantity,
            'remaining_returnable_quantity' => (string) $this->remaining_returnable_quantity,
            'unit_price' => (string) $this->unit_price,
            'line_subtotal' => (string) $this->line_subtotal,
            'discount_calculation_type' => $this->discount_calculation_type,
            'discount_rate' => (string) $this->discount_rate,
            'discount_amount' => (string) $this->discount_amount,
            'tax_calculation_type' => $this->tax_calculation_type,
            'tax_rate' => (string) $this->tax_rate,
            'tax_amount' => (string) $this->tax_amount,
            'charge_calculation_type' => $this->charge_calculation_type,
            'charge_rate' => (string) $this->charge_rate,
            'charge_amount' => (string) $this->charge_amount,
            'line_total' => (string) $this->line_total,
            'status' => $this->enumValue($this->status),
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
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
                $data[$field] = $model->{$field};
            }
        }

        return $data;
    }
}
