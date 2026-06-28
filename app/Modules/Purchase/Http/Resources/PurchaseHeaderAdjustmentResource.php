<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;

final class PurchaseHeaderAdjustmentResource extends PurchaseResource
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
            'finance_posting_profile_id' => $this->finance_posting_profile_id,
            'finance_account_id' => $this->finance_account_id,
            'cost_treatment' => $this->cost_treatment,
            'tax_treatment' => $this->tax_treatment,
            'mapping_source' => $this->mapping_source,
            'override_reason' => $this->override_reason,
            'finance_mapping' => $this->financeMapping(),
            'sort_order' => (int) $this->sort_order,
            'description' => $this->description,
        ];
    }

    private function financeMapping(): ?array
    {
        if ($this->finance_posting_profile_id === null && $this->finance_account_id === null) {
            return null;
        }

        return [
            'posting_profile_id' => $this->finance_posting_profile_id,
            'account_id' => $this->finance_account_id,
            'cost_treatment' => $this->cost_treatment,
            'tax_treatment' => $this->tax_treatment,
            'source' => $this->mapping_source,
        ];
    }
}
