<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
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
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
            'parent_location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'code' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'array'],
            'name.*' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.*' => ['sometimes', 'nullable', 'string'],
            'location_type' => ['sometimes', 'required', 'in:headquarters,office,branch,warehouse,factory,retail,distribution_center,transit,virtual,other'],
            'status' => ['sometimes', 'nullable', 'in:active,inactive,under_construction,closed'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'fax' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'operating_hours' => ['sometimes', 'nullable', 'array'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'area_sqm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'features' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
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
            'code.required' => 'Location code is required',
            'code.max' => 'Location code must not exceed 50 characters',
            'name.required' => 'Location name is required',
            'name.*.required' => 'Location name translation is required',
            'location_type.in' => 'Invalid location type',
            'country.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2)',
        ];
    }
}
