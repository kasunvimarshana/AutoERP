<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HR\Domain\Constants\EmployeeStatus;

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
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],

            'employee_code' => array_merge($required, ['string', 'max:60']),
            'first_name' => array_merge($required, ['string', 'max:120']),
            'last_name' => ['nullable', 'string', 'max:120'],
            'display_name' => ['nullable', 'string', 'max:180'],
            'gender' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'national_id_number' => ['nullable', 'string', 'max:120'],
            'passport_number' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:100'],

            'department_id' => ['nullable', 'integer', 'min:1', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'min:1', 'exists:designations,id'],
            'employment_type_id' => ['nullable', 'integer', 'min:1', 'exists:employment_types,id'],
            'reporting_manager_id' => ['nullable', 'integer', 'min:1', 'exists:employees,id'],
            'joining_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date'],
            'employment_status' => ['nullable', 'string', 'in:' . implode(',', EmployeeStatus::values())],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status_reason' => ['nullable', 'string', 'max:255'],

            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_type' => ['nullable', 'string', 'max:50'],
            'contacts.*.contact_name' => ['required_with:contacts', 'string', 'max:180'],
            'contacts.*.relationship' => ['nullable', 'string', 'max:120'],
            'contacts.*.email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:100'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:100'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.is_emergency' => ['nullable', 'boolean'],
            'contacts.*.is_active' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string'],
            'contacts.*.metadata' => ['nullable', 'array'],

            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['nullable', 'string', 'max:40'],
            'addresses.*.address_line_1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:120'],
            'addresses.*.state_province' => ['nullable', 'string', 'max:120'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:60'],
            'addresses.*.country_id' => ['nullable', 'integer', 'min:1', 'exists:countries,id'],
            'addresses.*.country_name' => ['nullable', 'string', 'max:120'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.is_active' => ['nullable', 'boolean'],
            'addresses.*.metadata' => ['nullable', 'array'],

            'employment_details' => ['nullable', 'array'],
            'employment_details.department_id' => ['nullable', 'integer', 'min:1', 'exists:departments,id'],
            'employment_details.designation_id' => ['nullable', 'integer', 'min:1', 'exists:designations,id'],
            'employment_details.employment_type_id' => ['nullable', 'integer', 'min:1', 'exists:employment_types,id'],
            'employment_details.employment_status' => [
                'nullable',
                'string',
                'in:' . implode(',', EmployeeStatus::values()),
            ],
            'employment_details.joining_date' => ['nullable', 'date'],
            'employment_details.probation_end_date' => ['nullable', 'date'],
            'employment_details.confirmation_date' => ['nullable', 'date'],
            'employment_details.leaving_date' => ['nullable', 'date'],
            'employment_details.reporting_manager_id' => ['nullable', 'integer', 'min:1', 'exists:employees,id'],
            'employment_details.work_location_id' => ['nullable', 'integer', 'min:1'],
            'employment_details.shift_id' => ['nullable', 'integer', 'min:1'],
            'employment_details.is_active' => ['nullable', 'boolean'],
            'employment_details.metadata' => ['nullable', 'array'],

            'salary_profile' => ['nullable', 'array'],
            'salary_profile.salary_type' => ['nullable', 'string', 'max:40'],
            'salary_profile.basic_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_profile.hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'salary_profile.overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'salary_profile.payment_method_id' => ['nullable', 'integer', 'min:1'],
            'salary_profile.bank_account_reference' => ['nullable', 'string', 'max:150'],
            'salary_profile.effective_from' => ['nullable', 'date'],
            'salary_profile.effective_to' => ['nullable', 'date'],
            'salary_profile.is_active' => ['nullable', 'boolean'],
            'salary_profile.metadata' => ['nullable', 'array'],

            'create_user' => ['nullable', 'boolean'],
            'link_user_id' => ['nullable', 'integer', 'min:1'],
            'user_access' => ['nullable', 'array'],
            'user_access.access_role' => ['nullable', 'string', 'max:60'],
            'user_access.is_primary' => ['nullable', 'boolean'],
            'user_access.invited' => ['nullable', 'boolean'],
            'user_access.metadata' => ['nullable', 'array'],
            'user_access.user' => ['nullable', 'array'],
            'user_access.user.name' => ['nullable', 'string', 'max:255'],
            'user_access.user.email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'user_access.user.password' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }
}
