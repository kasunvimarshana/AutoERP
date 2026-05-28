<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertEmployeeAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'address_type' => ['nullable', 'string', 'max:40'],
            'address_line_1' => array_merge($required, ['string', 'max:255']),
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => array_merge($required, ['string', 'max:120']),
            'state_province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:60'],
            'country_id' => ['nullable', 'integer', 'min:1', 'exists:countries,id'],
            'country_name' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
