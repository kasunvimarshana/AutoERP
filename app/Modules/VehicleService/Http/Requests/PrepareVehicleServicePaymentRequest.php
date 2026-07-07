<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;
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
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'payment_method_id' => ['required', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'external_bank_name' => ['nullable', 'string', 'max:150'],
            'external_bank_branch' => ['nullable', 'string', 'max:150'],
            'instrument_number' => ['nullable', 'string', 'max:150'],
            'instrument_date' => ['nullable', 'date'],
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
            amount: (string) $this->input('amount'),
            paymentMethodId: (int) $this->input('payment_method_id'),
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            referenceNumber: $this->stringOrNull('reference_number'),
            externalBankName: $this->stringOrNull('external_bank_name'),
            externalBankBranch: $this->stringOrNull('external_bank_branch'),
            instrumentNumber: $this->stringOrNull('instrument_number'),
            instrumentDate: $this->stringOrNull('instrument_date'),
            createdBy: $this->currentUserId(),
        );
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? trim((string) $this->input($key)) : null;
    }
}
