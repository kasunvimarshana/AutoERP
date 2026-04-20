<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:tenants,id'],
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'invoice_type' => ['required', 'string', 'max:255'],
            'invoice_id' => ['required', 'integer'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
