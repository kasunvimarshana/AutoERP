<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\Concerns\FormatsItemResources;

final class ItemCodeResource extends JsonResource
{
    use FormatsItemResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'variant' => $this->whenLoaded('variant', fn () => $this->namedResource($this->variant)),
            'code_type' => $this->enumValue($this->code_type),
            'code' => $this->code,
            'party_type' => $this->party_type,
            'is_primary' => (bool) $this->is_primary,
        ];
    }
}
