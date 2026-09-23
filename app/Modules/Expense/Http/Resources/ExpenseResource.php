<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'expense_number' => (string) $this->expense_number,
            'expense_date' => $this->expense_date?->toDateString(),
            'amount' => (string) $this->amount,
            'exchange_rate' => (string) $this->exchange_rate,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'expense_type' => [
                'id' => (int) $this->expense_type_id,
                'code' => (string) $this->expense_type_code_snapshot,
                'name' => (string) $this->expense_type_name_snapshot,
            ],
            'organization_unit' => $this->whenLoaded('organizationUnit', fn (): array => [
                'id' => (int) $this->organizationUnit->getKey(),
                'code' => (string) $this->organizationUnit->code,
                'name' => (string) $this->organizationUnit->name,
            ]),
            'currency' => $this->whenLoaded('currency', fn (): ?array => $this->currency === null ? null : [
                'id' => (int) $this->currency->getKey(),
                'code' => (string) $this->currency->code,
                'name' => (string) $this->currency->name,
                'symbol' => $this->currency->symbol,
            ]),
            'payment_method' => [
                'id' => (int) $this->payment_method_id_snapshot,
                'code' => (string) $this->payment_method_code_snapshot,
                'name' => (string) $this->payment_method_name_snapshot,
                'type' => (string) $this->payment_method_type_snapshot,
            ],
            'reference_number' => $this->reference_number,
            'instrument_number' => $this->instrument_number,
            'instrument_date' => $this->instrument_date?->toDateString(),
            'external_bank_name' => $this->external_bank_name,
            'notes' => $this->notes,
            'finance_posting_reference' => $this->finance_posting_reference,
            'finance_reversal_reference' => $this->finance_reversal_reference,
            'reversal_date' => $this->reversal_date,
            'reversal_reason' => $this->reversal_reason,
            'posted_at' => $this->posted_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
        ];
    }
}
