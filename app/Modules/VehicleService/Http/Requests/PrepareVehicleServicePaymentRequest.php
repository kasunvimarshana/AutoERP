<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;

final class PrepareVehicleServicePaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'invoice_id' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'payment_method_id' => ['required', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'internal_bank_account_id' => ['nullable', 'integer', 'min:1'],
            'external_bank_name' => ['nullable', 'string', 'max:150'],
            'external_bank_branch' => ['nullable', 'string', 'max:150'],
            'instrument_number' => ['nullable', 'string', 'max:150'],
            'instrument_date' => ['nullable', 'date'],
            'deposit_date' => ['nullable', 'date'],
            'realized_date' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): VehicleServicePaymentData
    {
        return new VehicleServicePaymentData(
            invoiceId: (int) $this->input('invoice_id'),
            paymentDate: (string) $this->input('payment_date'),
            amount: (string) $this->input('amount'),
            paymentMethodId: (int) $this->input('payment_method_id'),
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            referenceNumber: $this->filled('reference_number') ? (string) $this->input('reference_number') : null,
            internalBankAccountId: $this->filled('internal_bank_account_id') ? (int) $this->input('internal_bank_account_id') : null,
            externalBankName: $this->filled('external_bank_name') ? (string) $this->input('external_bank_name') : null,
            externalBankBranch: $this->filled('external_bank_branch') ? (string) $this->input('external_bank_branch') : null,
            instrumentNumber: $this->filled('instrument_number') ? (string) $this->input('instrument_number') : null,
            instrumentDate: $this->filled('instrument_date') ? (string) $this->input('instrument_date') : null,
            depositDate: $this->filled('deposit_date') ? (string) $this->input('deposit_date') : null,
            realizedDate: $this->filled('realized_date') ? (string) $this->input('realized_date') : null,
            metadata: $this->filled('metadata') ? (array) $this->input('metadata') : null,
            createdBy: $this->currentUserId(),
        );
    }
}
