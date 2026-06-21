<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RentalDepositPaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method_id' => ['required', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'instrument_number' => ['nullable', 'string', 'max:100'],
            'instrument_date' => ['nullable', 'date'],
            'internal_bank_account_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,10', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
