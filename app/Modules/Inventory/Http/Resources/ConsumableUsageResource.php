<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConsumableUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'number' => (string) $this->movement_number,
            'date' => $this->movement_date?->toDateString(),
            'item' => $this->whenLoaded('item', fn (): array => ['id' => $this->item->getKey(), 'name' => $this->item->name, 'code' => $this->item->code]),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => ['id' => $this->warehouse->getKey(), 'name' => $this->warehouse->name]),
            'location' => $this->whenLoaded('warehouseLocation', fn (): ?array => $this->warehouseLocation === null ? null : ['id' => $this->warehouseLocation->getKey(), 'name' => $this->warehouseLocation->name]),
            'variant' => $this->whenLoaded('variant', fn (): ?array => $this->variant === null ? null : ['id' => $this->variant->getKey(), 'name' => $this->variant->name]),
            'batch' => $this->whenLoaded('batch', fn (): ?array => $this->batch === null ? null : ['id' => $this->batch->getKey(), 'name' => $this->batch->batch_number]),
            'uom' => $this->whenLoaded('baseUom', fn (): ?array => $this->baseUom === null ? null : ['id' => $this->baseUom->getKey(), 'name' => $this->baseUom->name]),
            'quantity' => (string) $this->quantity,
            'total_cost' => (string) $this->total_cost,
            'status' => $this->status->value,
            'notes' => $this->description,
            'posted_at' => $this->posted_at?->toIso8601String(),
        ];
    }
}
