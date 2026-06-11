<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ItemBaseUomRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'item' => $this->whenLoaded('item', fn () => [
                'id' => (int) $this->item->getKey(),
                'code' => $this->item->code,
                'name' => $this->item->name,
            ]),
            'old_base_uom' => $this->whenLoaded('oldBaseUom', fn () => $this->uom($this->oldBaseUom)),
            'new_base_uom' => $this->whenLoaded('newBaseUom', fn () => $this->uom($this->newBaseUom)),
            'conversion_factor' => (string) $this->conversion_factor,
            'effective_at' => $this->effective_at?->toISOString(),
            'reason' => $this->reason,
            'status' => $this->status instanceof BackedEnum ? $this->status->value : (string) $this->status,
            'validation_summary' => $this->validation_summary,
            'applied_at' => $this->applied_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function uom(?Model $uom): ?array
    {
        if ($uom === null) {
            return null;
        }

        return [
            'id' => (int) $uom->getKey(),
            'code' => $uom->getAttribute('code'),
            'name' => $uom->getAttribute('name'),
            'symbol' => $uom->getAttribute('symbol'),
        ];
    }
}
