<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\Concerns\FormatsItemResources;

final class ItemCategoryResource extends JsonResource
{
    use FormatsItemResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'parent' => $this->whenLoaded('parent', fn () => $this->namedResource($this->parent)),
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
