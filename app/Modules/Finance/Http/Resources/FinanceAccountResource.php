<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class FinanceAccountResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => $this->description,
            'normal_balance' => $this->enum($this->normal_balance),
            'opening_balance' => (string) $this->opening_balance,
            'current_balance' => (string) $this->current_balance,
            'is_control_account' => (bool) $this->is_control_account,
            'is_posting_account' => (bool) $this->is_posting_account,
            'is_cash_account' => (bool) $this->is_cash_account,
            'is_bank_account' => (bool) $this->is_bank_account,
            'is_tax_account' => (bool) $this->is_tax_account,
            'is_system' => (bool) $this->is_system,
            'is_active' => (bool) $this->is_active,
            'account_type' => $this->whenLoaded('accountType'),
            'account_category' => $this->whenLoaded('accountCategory'),
            'parent' => FinanceAccountSummaryResource::make($this->whenLoaded('parent')),
            'children' => FinanceAccountSummaryResource::collection($this->whenLoaded('children')),
            'balances' => $this->whenLoaded('balances'),
            'can_edit' => true,
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
