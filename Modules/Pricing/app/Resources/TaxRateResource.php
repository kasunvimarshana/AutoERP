<?php

declare(strict_types=1);

namespace Modules\Pricing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TaxRate Resource
 */
class TaxRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'rate' => (string) $this->rate,
            'jurisdiction' => $this->jurisdiction,
            'product_category' => $this->product_category,
            'is_compound' => $this->is_compound,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'effective_date' => $this->effective_date?->toISOString(),
            'expiry_date' => $this->expiry_date?->toISOString(),
            'is_effective_now' => $this->isEffectiveNow(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
