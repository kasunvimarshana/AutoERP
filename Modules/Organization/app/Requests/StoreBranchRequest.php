<?php

declare(strict_types=1);

namespace Modules\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organization\Enums\BranchStatus;

/**
 * Store Branch Request
 *
 * Validates data for creating a new branch
 */
class StoreBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_code' => ['sometimes', 'string', 'max:50', 'unique:branches,branch_code'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', BranchStatus::values())],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'], // ISO 3166-1 alpha-2
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'operating_hours' => ['nullable', 'array'],
            'services_offered' => ['nullable', 'array'],
            'capacity_vehicles' => ['nullable', 'integer', 'min:0'],
            'bay_count' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'branch_code' => 'branch code',
            'manager_name' => 'manager name',
            'postal_code' => 'postal code',
            'capacity_vehicles' => 'vehicle capacity',
            'bay_count' => 'bay count',
            'operating_hours' => 'operating hours',
            'services_offered' => 'services offered',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.exists' => 'The selected organization does not exist.',
            'status.in' => 'The selected branch status is invalid.',
            'country.size' => 'The country code must be exactly 2 characters (ISO 3166-1 alpha-2).',
            'latitude.between' => 'The latitude must be between -90 and 90 degrees.',
            'longitude.between' => 'The longitude must be between -180 and 180 degrees.',
        ];
    }
}
