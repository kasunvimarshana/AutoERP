<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Payment\DTOs\PaymentLineData;

trait BuildsPaymentLines
{
    public function paymentLineRules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.payment_method_id' => ['required', 'integer', 'min:1'],
            'lines.*.reference_number' => ['nullable', 'string', 'max:150'],
            'lines.*.amount' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.instrument_direction' => ['nullable', Rule::in(['received', 'issued'])],
            'lines.*.external_bank_name' => ['nullable', 'string', 'max:150'],
            'lines.*.external_bank_branch' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_number' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_date' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string'],
            'lines.*.cleared_amount' => ['prohibited'],
            'lines.*.status' => ['prohibited'],
            'lines.*.deposit_date' => ['prohibited'],
            'lines.*.realized_date' => ['prohibited'],
            'lines.*.clearing_date' => ['prohibited'],
            'lines.*.bounced_date' => ['prohibited'],
            'lines.*.returned_date' => ['prohibited'],
            'lines.*.cancellation_reason' => ['prohibited'],
            'lines.*.metadata' => ['prohibited'],
        ];
    }

    public function paymentLineData(): array
    {
        return array_map(static fn (array $row): PaymentLineData => new PaymentLineData(
            amount: (string) $row['amount'],
            paymentMethodId: isset($row['payment_method_id']) ? (int) $row['payment_method_id'] : null,
            referenceNumber: $row['reference_number'] ?? null,
            notes: $row['notes'] ?? null,
            instrumentDirection: $row['instrument_direction'] ?? null,
            externalBankName: $row['external_bank_name'] ?? null,
            externalBankBranch: $row['external_bank_branch'] ?? null,
            instrumentNumber: $row['instrument_number'] ?? null,
            instrumentDate: $row['instrument_date'] ?? null,
        ), $this->input('lines'));
    }
}
