<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\Concerns\FormatsItemResources;

final class ItemUnitResource extends JsonResource
{
    use FormatsItemResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'uom' => $this->whenLoaded('uom', fn () => $this->namedResource($this->uom, true)),
            'unit_role' => $this->enumValue($this->unit_role),
            'conversion_factor' => (string) $this->conversion_factor,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
