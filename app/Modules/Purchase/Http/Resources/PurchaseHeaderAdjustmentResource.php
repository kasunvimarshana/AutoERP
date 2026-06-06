<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseHeaderAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'name' => $this->name,
            'adjustment_type' => $this->enumValue($this->adjustment_type),
            'effect' => $this->enumValue($this->effect),
            'calculation_type' => $this->enumValue($this->calculation_type),
            'calculation_base' => $this->enumValue($this->calculation_base),
            'rate' => (string) $this->rate,
            'amount' => (string) $this->amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'returned_amount' => (string) $this->returned_amount,
            'remaining_amount' => (string) $this->remaining_amount,
            'allocation_method' => $this->enumValue($this->allocation_method),
            'is_allocatable' => (bool) $this->is_allocatable,
            'sort_order' => (int) $this->sort_order,
            'description' => $this->description,
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
