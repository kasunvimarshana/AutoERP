<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCustomerCreditProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'credit_hold' => ['nullable', 'boolean'],
            'credit_hold_reason' => ['nullable', 'string', 'max:255'],
            'allow_credit_override' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
