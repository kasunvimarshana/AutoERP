<?php

declare(strict_types=1);

namespace App\Http\Resources\Taxation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'base_amount' => $this->base_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'is_inclusive' => $this->is_inclusive,
            'tax_breakdown' => $this->tax_breakdown,
            'applied_taxes' => $this->applied_taxes,
            'exemptions_applied' => $this->exemptions_applied,
            'customer_id' => $this->customer_id,
            'product_id' => $this->product_id,
            'branch_id' => $this->branch_id,
            'tax_jurisdiction_id' => $this->tax_jurisdiction_id,
            'calculation_method' => $this->calculation_method,
            'effective_tax_rate' => $this->getEffectiveTaxRate(),
            'metadata' => $this->metadata,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
