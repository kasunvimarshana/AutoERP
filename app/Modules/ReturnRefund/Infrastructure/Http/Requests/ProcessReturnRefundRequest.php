<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessReturnRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'uuid'],
            'rental_transaction_id' => ['required', 'uuid'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'is_damaged' => ['required', 'boolean'],
            'damage_notes' => ['nullable', 'string'],
            'damage_charge' => ['required', 'numeric', 'min:0'],
            'fuel_adjustment_charge' => ['required', 'numeric', 'min:0'],
            'late_return_charge' => ['required', 'numeric', 'min:0'],
        ];
    }
}
