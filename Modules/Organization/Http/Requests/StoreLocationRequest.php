<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
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
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'parent_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'location_type' => ['required', 'in:headquarters,office,branch,warehouse,factory,retail,distribution_center,transit,virtual,other'],
            'status' => ['nullable', 'in:active,inactive,under_construction,closed'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'operating_hours' => ['nullable', 'array'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'area_sqm' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:0'],
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
            'organization_id.required' => 'Organization is required',
            'organization_id.exists' => 'Selected organization does not exist',
            'code.required' => 'Location code is required',
            'code.max' => 'Location code must not exceed 50 characters',
            'name.required' => 'Location name is required',
            'name.*.required' => 'Location name translation is required',
            'location_type.required' => 'Location type is required',
            'location_type.in' => 'Invalid location type',
            'country.size' => 'Country code must be 2 characters (ISO 3166-1 alpha-2)',
        ];
    }
}
