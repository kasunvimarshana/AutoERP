<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'      => ['required', 'integer', 'min:1'],
            'party_type'     => ['sometimes', 'string', 'in:individual,company'],
            'name'           => ['sometimes', 'string', 'max:255'],
            'tax_number'     => ['nullable', 'string', 'max:100'],
            'email'          => ['nullable', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city'           => ['nullable', 'string', 'max:100'],
            'state_province' => ['nullable', 'string', 'max:100'],
            'postal_code'    => ['nullable', 'string', 'max:20'],
            'country_code'   => ['nullable', 'string', 'max:3'],
            'is_active'      => ['nullable', 'boolean'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
