<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RefundRentalDepositRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_requirement_version' => ['required', 'integer', 'min:1'],
            'payment_id' => ['required', 'integer', 'min:1'],
            'expected_payment_version' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'refund_date' => ['required', 'date'],
            'payment_method_id' => ['nullable', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'external_bank_name' => ['nullable', 'string', 'max:150'],
            'external_bank_branch' => ['nullable', 'string', 'max:150'],
            'instrument_number' => ['nullable', 'string', 'max:100'],
            'instrument_date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'internal_bank_account_id' => ['prohibited'],
            'bank_account_id' => ['prohibited'],
        ];
    }
}
