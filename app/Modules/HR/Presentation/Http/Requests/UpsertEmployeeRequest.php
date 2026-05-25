<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'user_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'registration_number' => array_merge($required, ['string', 'max:255']),
            'department_id' => ['nullable', 'integer', 'min:1', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'min:1', 'exists:designations,id'],
            'employment_type_id' => ['nullable', 'integer', 'min:1', 'exists:employment_types,id'],
            'hire_date' => array_merge($required, ['date']),
            'confirmation_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'termination_reason' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'personal_email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string'],
            'address_line2' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', 'min:1', 'exists:countries,id'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'social_security_number' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_routing_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:0'],
            'updated_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}