<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\UpdateEmployeeData;
use Modules\Hr\Enums\Gender;

final class UpdateEmployeeRequest extends TenantScopedRequest
{
    private const EDITABLE_FIELDS = [
        'organization_unit_id',
        'code',
        'first_name',
        'middle_name',
        'last_name',
        'display_name',
        'email',
        'phone',
        'mobile',
        'department_id',
        'designation_id',
        'employment_type_id',
        'reporting_manager_id',
        'joined_date',
        'resigned_date',
        'date_of_birth',
        'gender',
        'default_hourly_rate',
        'default_daily_rate',
        'default_service_rate',
        'notes',
        'metadata',
    ];

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'row_version' => ['required', 'integer', 'min:1'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'first_name' => ['sometimes', 'required', 'string', 'max:150'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'display_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:50'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'designation_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'employment_type_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'reporting_manager_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'joined_date' => ['sometimes', 'nullable', 'date'],
            'resigned_date' => ['sometimes', 'nullable', 'date'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'nullable', Rule::enum(Gender::class)],
            'default_hourly_rate' => ['sometimes', 'decimal:0,6', 'gte:0'],
            'default_daily_rate' => ['sometimes', 'decimal:0,6', 'gte:0'],
            'default_service_rate' => ['sometimes', 'decimal:0,6', 'gte:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function toData(): UpdateEmployeeData
    {
        $v = $this->validated();
        $s = fn (string $k): ?string => isset($v[$k]) && $v[$k] !== '' ? (string) $v[$k] : null;
        $i = fn (string $k): ?int => isset($v[$k]) && $v[$k] !== '' ? (int) $v[$k] : null;

        return new UpdateEmployeeData(
            rowVersion: (int) $v['row_version'],
            provided: array_values(array_intersect(self::EDITABLE_FIELDS, array_keys($v))),
            organizationUnitId: $i('organization_unit_id'),
            code: $s('code'),
            firstName: $s('first_name'),
            middleName: $s('middle_name'),
            lastName: $s('last_name'),
            displayName: $s('display_name'),
            email: $s('email'),
            phone: $s('phone'),
            mobile: $s('mobile'),
            departmentId: $i('department_id'),
            designationId: $i('designation_id'),
            employmentTypeId: $i('employment_type_id'),
            reportingManagerId: $i('reporting_manager_id'),
            joinedDate: $s('joined_date'),
            resignedDate: $s('resigned_date'),
            dateOfBirth: $s('date_of_birth'),
            gender: isset($v['gender']) && $v['gender'] !== '' ? Gender::from((string) $v['gender']) : null,
            defaultHourlyRate: $s('default_hourly_rate'),
            defaultDailyRate: $s('default_daily_rate'),
            defaultServiceRate: $s('default_service_rate'),
            notes: $s('notes'),
            metadata: $v['metadata'] ?? null,
        );
    }
}
