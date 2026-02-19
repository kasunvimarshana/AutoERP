<?php

declare(strict_types=1);

namespace Modules\Pricing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DiscountRule Resource
 */
class DiscountRuleResource extends JsonResource
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
            'type' => $this->type,
            'value' => $this->value ? (string) $this->value : null,
            'max_discount_amount' => $this->max_discount_amount ? (string) $this->max_discount_amount : null,
            'min_purchase_amount' => $this->min_purchase_amount ? (string) $this->min_purchase_amount : null,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'conditions' => $this->conditions,
            'applicable_products' => $this->applicable_products,
            'applicable_categories' => $this->applicable_categories,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'usage_remaining' => $this->hasUsageRemaining(),
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'is_valid_now' => $this->isValidNow(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
