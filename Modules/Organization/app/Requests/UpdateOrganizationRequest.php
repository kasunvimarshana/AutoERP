<?php

declare(strict_types=1);

namespace Modules\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organization\Enums\OrganizationStatus;
use Modules\Organization\Enums\OrganizationType;

/**
 * Update Organization Request
 *
 * Validates data for updating an existing organization
 */
class UpdateOrganizationRequest extends FormRequest
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
        $organizationId = $this->route('organization') ?? $this->route('id');

        return [
            'organization_number' => ['sometimes', 'string', 'max:50', 'unique:organizations,organization_number,'.$organizationId],
            'name' => ['sometimes', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', OrganizationType::values())],
            'status' => ['sometimes', 'string', 'in:'.implode(',', OrganizationStatus::values())],
            'tax_id' => ['nullable', 'string', 'max:100', 'unique:organizations,tax_id,'.$organizationId],
            'registration_number' => ['nullable', 'string', 'max:100', 'unique:organizations,registration_number,'.$organizationId],
            'email' => ['nullable', 'email', 'max:255', 'unique:organizations,email,'.$organizationId],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'], // ISO 3166-1 alpha-2
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
            'organization_number' => 'organization number',
            'legal_name' => 'legal name',
            'tax_id' => 'tax ID',
            'registration_number' => 'registration number',
            'postal_code' => 'postal code',
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
            'type.in' => 'The selected organization type is invalid.',
            'status.in' => 'The selected organization status is invalid.',
            'country.size' => 'The country code must be exactly 2 characters (ISO 3166-1 alpha-2).',
        ];
    }
}
