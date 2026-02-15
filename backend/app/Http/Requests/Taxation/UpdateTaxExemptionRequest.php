<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxExemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'exemption_number' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['sometimes', 'required', 'in:customer,product,product_category,vendor'],
            'entity_id' => ['sometimes', 'required', 'integer'],
            'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'tax_group_id' => ['nullable', 'exists:tax_groups,id'],
            'exemption_type' => ['sometimes', 'required', 'in:full,partial'],
            'exemption_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reason' => ['nullable', 'string'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'valid_from' => ['sometimes', 'required', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Exemption name is required',
            'entity_type.required' => 'Entity type is required',
            'entity_type.in' => 'Invalid entity type',
            'entity_id.required' => 'Entity ID is required',
            'exemption_type.required' => 'Exemption type is required',
            'exemption_type.in' => 'Invalid exemption type',
            'valid_from.required' => 'Valid from date is required',
            'valid_to.after' => 'Valid to date must be after valid from date',
        ];
    }
}
