<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $taxGroupId = $this->route('tax_group');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:tax_groups,code,' . $taxGroupId],
            'description' => ['nullable', 'string'],
            'application_type' => ['sometimes', 'required', 'in:compound,stacked,highest,average'],
            'is_inclusive' => ['boolean'],
            'is_active' => ['boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tax group name is required',
            'code.required' => 'Tax group code is required',
            'code.unique' => 'This tax group code already exists',
            'application_type.required' => 'Application type is required',
            'application_type.in' => 'Invalid application type',
            'effective_to.after' => 'Effective to date must be after effective from date',
        ];
    }
}
