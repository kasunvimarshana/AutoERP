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
            'scope' => $this->organization_unit_id === null ? 'Tenant' : 'Organization Unit',
            'organization_unit' => $this->whenLoaded('organizationUnit', fn () => $this->namedResource($this->organizationUnit)),
            'variant' => $this->whenLoaded('variant', fn () => $this->namedResource($this->variant)),
            'price_type' => $this->enumValue($this->price_type),
            'currency' => $this->whenLoaded('currency', fn () => $this->namedResource($this->currency, true)),
            'uom' => $this->whenLoaded('uom', fn () => $this->namedResource($this->uom, true)),
            'amount' => (string) $this->amount,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'revision_no' => (int) $this->revision_no,
            'recorded_from' => $this->recorded_from?->toISOString(),
            'recorded_to' => $this->recorded_to?->toISOString(),
            'correction_reason' => $this->correction_reason,
            'row_version' => (int) $this->row_version,
            'is_current_revision' => $this->recorded_to === null,
        ];
    }
}
