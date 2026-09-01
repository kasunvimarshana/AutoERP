<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InventoryBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'batch_number' => $this->batch_number,
            'lot_number' => $this->lot_number,
            'manufacture_date' => $this->manufacture_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'item' => $this->whenLoaded('item', fn (): array => [
                'id' => (int) $this->item->getKey(),
                'code' => $this->item->code,
                'name' => $this->item->name,
                'tracking_type' => $this->item->tracking_type instanceof \BackedEnum ? $this->item->tracking_type->value : (string) $this->item->tracking_type,
            ]),
            'variant' => $this->whenLoaded('variant', fn (): ?array => $this->variant === null ? null : [
                'id' => (int) $this->variant->getKey(),
                'code' => $this->variant->code,
                'name' => $this->variant->name,
            ]),
            'available_stock_quantity' => $this->when(
                array_key_exists('available_stock_quantity', $this->resource->getAttributes()),
                fn () => (string) $this->getAttribute('available_stock_quantity'),
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
