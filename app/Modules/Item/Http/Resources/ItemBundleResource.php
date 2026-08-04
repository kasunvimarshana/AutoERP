<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\Concerns\FormatsItemResources;

final class ItemBundleResource extends JsonResource
{
    use FormatsItemResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'child_item' => $this->whenLoaded('childItem', fn () => (new ItemSummaryResource($this->childItem))->resolve($request)),
            'child_variant' => $this->whenLoaded('childVariant', fn () => $this->namedResource($this->childVariant)),
            'quantity' => (string) $this->quantity,
            'uom' => $this->whenLoaded('uom', fn () => $this->namedResource($this->uom, true)),
            'line_type' => $this->line_type,
            'unit_cost' => (string) $this->unit_cost,
            'default_workforce_role' => $this->default_workforce_role,
            'is_required' => (bool) $this->is_required,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
