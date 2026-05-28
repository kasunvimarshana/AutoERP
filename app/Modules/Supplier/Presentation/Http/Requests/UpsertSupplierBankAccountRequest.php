<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSupplierBankAccountRequest extends FormRequest
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
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'account_name' => array_merge($required, ['string', 'max:180']),
            'account_number' => array_merge($required, ['string', 'max:120']),
            'iban' => ['nullable', 'string', 'max:120'],
            'swift_code' => ['nullable', 'string', 'max:50'],
            'bank_name' => array_merge($required, ['string', 'max:180']),
            'branch_name' => ['nullable', 'string', 'max:180'],
            'bank_code' => ['nullable', 'string', 'max:60'],
            'branch_code' => ['nullable', 'string', 'max:60'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
