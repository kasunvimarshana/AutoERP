<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
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
        return [
            'parent_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'organization_type' => ['required', 'in:headquarters,subsidiary,branch,division,department,other'],
            'status' => ['nullable', 'in:active,inactive,suspended,closed'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'settings' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Organization code is required',
            'code.max' => 'Organization code must not exceed 50 characters',
            'name.required' => 'Organization name is required',
            'name.*.required' => 'Organization name translation is required',
            'parent_id.exists' => 'Selected parent organization does not exist',
            'organization_type.required' => 'Organization type is required',
            'organization_type.in' => 'Invalid organization type',
            'country.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2)',
        ];
    }
}
