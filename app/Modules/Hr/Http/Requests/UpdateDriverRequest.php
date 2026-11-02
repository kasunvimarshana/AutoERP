<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\Gender;

final class UpdateDriverRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['nullable', 'string', 'max:50'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'nic_passport' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'date_of_birth' => ['nullable', 'date'],
            'joined_date' => ['nullable', 'date'],
            'driving_license_number' => ['sometimes', 'required', 'string', 'max:80'],
            'driving_license_classes' => ['nullable', 'array'],
            'driving_license_classes.*' => ['string', 'max:50'],
            'driving_license_issued' => ['nullable', 'date'],
            'driving_license_expiry' => ['nullable', 'date'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'default_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'work_start_time' => ['nullable', 'string', 'max:20'],
            'work_end_time' => ['nullable', 'string', 'max:20'],
            'standard_shift_hours' => ['nullable', 'numeric', 'min:1', 'max:24'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
            'availability_status' => ['nullable', Rule::enum(EmployeeAvailabilityStatus::class)],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
