<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', 'in:cash,bank_transfer,card,cheque,other'],
            'account_id' => ['sometimes', 'nullable', 'integer', 'exists:accounts,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
