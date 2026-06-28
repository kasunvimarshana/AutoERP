<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\Concerns\FormatsItemResources;

final class ItemPriceResource extends JsonResource
{
    use FormatsItemResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'variant' => $this->whenLoaded('variant', fn () => $this->namedResource($this->variant)),
            'price_type' => $this->enumValue($this->price_type),
            'currency' => $this->whenLoaded('currency', fn () => $this->namedResource($this->currency, true)),
            'uom' => $this->whenLoaded('uom', fn () => $this->namedResource($this->uom, true)),
            'amount' => (string) $this->amount,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
