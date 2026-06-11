<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'tax_type' => $this->tax_type,
            'calculation_method' => $this->calculation_method,
            'is_withholding' => (bool) $this->is_withholding,
            'recoverable' => (bool) $this->recoverable,
            'payable' => (bool) $this->payable,
            'receivable' => (bool) $this->receivable,
            'active' => (bool) $this->active,
            'rates' => $this->whenLoaded('rates', fn () => $this->rates->map(fn ($rate): array => [
                'id' => $rate->id,
                'rate' => (string) $rate->rate,
                'effective_from' => $rate->effective_from?->toDateString(),
                'effective_to' => $rate->effective_to?->toDateString(),
                'active' => (bool) $rate->active,
            ])->values()->all()),
        ];
    }
}
