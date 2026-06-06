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
            'ordered_quantity' => (string) $this->ordered_quantity,
            'received_quantity' => (string) $this->received_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'returned_quantity' => (string) $this->returned_quantity,
            'cancelled_quantity' => (string) $this->cancelled_quantity,
            'remaining_quantity' => (string) $this->remaining_quantity,
            'unit_price' => (string) $this->unit_price,
            'discount_amount' => (string) $this->discount_amount,
            'tax_amount' => (string) $this->tax_amount,
            'charge_amount' => (string) $this->charge_amount,
            'line_total' => (string) $this->line_total,
            'status' => $this->enumValue($this->status),
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
