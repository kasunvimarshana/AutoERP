<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCustomerTaxProfileRequest extends FormRequest
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
            'tax_registration_number' => ['nullable', 'string', 'max:120'],
            'vat_number' => ['nullable', 'string', 'max:120'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'tax_exempt' => ['nullable', 'boolean'],
            'exemption_certificate_reference' => ['nullable', 'string', 'max:120'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
