<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxJurisdictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:tax_jurisdictions,code'],
            'jurisdiction_type' => ['required', 'in:country,state,city,postal_code,custom'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'state_code' => ['nullable', 'string', 'max:10'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'tax_group_id' => ['nullable', 'exists:tax_groups,id'],
            'priority' => ['integer', 'min:0'],
            'is_reverse_charge' => ['boolean'],
            'is_active' => ['boolean'],
            'rules' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Jurisdiction name is required',
            'code.required' => 'Jurisdiction code is required',
            'code.unique' => 'This jurisdiction code already exists',
            'jurisdiction_type.required' => 'Jurisdiction type is required',
            'jurisdiction_type.in' => 'Invalid jurisdiction type',
            'country_code.size' => 'Country code must be 2 characters',
        ];
    }
}
