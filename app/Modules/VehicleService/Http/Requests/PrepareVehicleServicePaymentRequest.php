<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;
use Modules\VehicleService\DTOs\VehicleServicePaymentLineData;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class PrepareVehicleServicePaymentRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'invoice_id' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.amount' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.payment_method_id' => ['required', 'integer', 'min:1'],
            'lines.*.reference_number' => ['nullable', 'string', 'max:150'],
            'lines.*.external_bank_name' => ['nullable', 'string', 'max:150'],
            'lines.*.external_bank_branch' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_number' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_date' => ['nullable', 'date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'internal_bank_account_id' => ['prohibited'],
            'bank_account_id' => ['prohibited'],
            'deposit_date' => ['prohibited'],
            'realized_date' => ['prohibited'],
            'metadata' => ['prohibited'],
        ];
    }

    public function toData(): VehicleServicePaymentData
    {
        return new VehicleServicePaymentData(
            expectedVersion: (int) $this->input('expected_version'),
            invoiceId: (int) $this->input('invoice_id'),
            paymentDate: (string) $this->input('payment_date'),
            lines: array_map(fn (array $line): VehicleServicePaymentLineData => new VehicleServicePaymentLineData(
                amount: (string) $line['amount'],
                paymentMethodId: (int) $line['payment_method_id'],
                referenceNumber: $this->stringOrNullFrom($line, 'reference_number'),
                externalBankName: $this->stringOrNullFrom($line, 'external_bank_name'),
                externalBankBranch: $this->stringOrNullFrom($line, 'external_bank_branch'),
                instrumentNumber: $this->stringOrNullFrom($line, 'instrument_number'),
                instrumentDate: $this->stringOrNullFrom($line, 'instrument_date'),
            ), $this->input('lines', [])),
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            createdBy: $this->currentUserId(),
        );
    }

    private function stringOrNullFrom(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
