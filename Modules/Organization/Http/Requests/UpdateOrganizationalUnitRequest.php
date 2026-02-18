<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationalUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $unitId = $this->route('organizational_unit');

        return [
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'parent_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('organizational_units', 'code')
                    ->where('tenant_id', auth()->user()?->tenant_id ?? session('tenant_id'))
                    ->ignore($unitId)
            ],
            'name' => ['sometimes', 'required', 'array'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'unit_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['division', 'department', 'team', 'group', 'project', 'other'])
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['active', 'inactive', 'suspended'])
            ],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'settings' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'Organization is required',
            'organization_id.exists' => 'Selected organization does not exist',
            'location_id.exists' => 'Selected location does not exist',
            'parent_unit_id.exists' => 'Selected parent unit does not exist',
            'code.required' => 'Unit code is required',
            'code.unique' => 'This unit code is already in use',
            'code.regex' => 'Unit code must contain only uppercase letters, numbers, hyphens, and underscores',
            'name.required' => 'Unit name is required',
            'name.en.required_with' => 'Unit name in English is required',
            'unit_type.required' => 'Unit type is required',
            'unit_type.in' => 'Invalid unit type',
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status',
            'manager_id.exists' => 'Selected manager does not exist',
            'email.email' => 'Please provide a valid email address',
            'phone.max' => 'Phone number must not exceed 20 characters',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'location_id' => 'location',
            'parent_unit_id' => 'parent unit',
            'unit_type' => 'unit type',
            'manager_id' => 'manager',
        ];
    }
}
