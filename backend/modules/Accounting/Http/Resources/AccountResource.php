<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_code' => $this->code,
            'account_name' => $this->name,
            'account_description' => $this->description,
            'account_classification' => [
                'type' => $this->type?->value,
                'type_label' => $this->getTypeLabel(),
            ],
            'hierarchy_info' => $this->buildHierarchyInfo(),
            'financial_data' => [
                'current_balance' => $this->formatMoney($this->balance),
                'currency' => $this->currency_code,
            ],
            'account_status' => [
                'is_active' => $this->is_active,
                'is_system_account' => $this->is_system,
                'can_be_modified' => !$this->is_system,
            ],
            'sub_accounts' => $this->when(
                $this->relationLoaded('children'),
                fn() => self::collection($this->children)
            ),
            'ledger_entries' => $this->when(
                $this->relationLoaded('journalEntryLines'),
                fn() => [
                    'total_entries' => $this->journalEntryLines->count(),
                ]
            ),
            'timestamps' => [
                'created' => $this->created_at?->toIso8601String(),
                'last_modified' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function buildHierarchyInfo(): array
    {
        $info = [
            'has_parent' => !is_null($this->parent_id),
            'parent_account_id' => $this->parent_id,
        ];

        if ($this->relationLoaded('parent') && $this->parent) {
            $info['parent_account_code'] = $this->parent->code;
            $info['parent_account_name'] = $this->parent->name;
        }

        return $info;
    }

    private function getTypeLabel(): string
    {
        return match($this->type?->value) {
            'asset' => 'Asset Account',
            'liability' => 'Liability Account',
            'equity' => 'Equity Account',
            'revenue' => 'Revenue Account',
            'expense' => 'Expense Account',
            default => 'Unknown Type',
        };
    }

    private function formatMoney($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}
