<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\Gender;
use Modules\Hr\Http\Requests\Concerns\MapsEmployeeData;

class StoreEmployeeRequest extends TenantScopedRequest
{
    use MapsEmployeeData;
    public function rules(): array { return self::employeeRules(); }
    public function toData(): CreateEmployeeData { return $this->mapEmployeeData($this->validated()); }
    public static function employeeRules(string $prefix = ''): array
    {
        $key = fn (string $name) => $prefix.$name;
        return [
            $key('tenant_id') => $prefix === '' ? ['required', 'integer', 'min:1'] : ['prohibited'],
            $key('organization_unit_id') => $prefix === '' ? ['nullable', 'integer', 'min:1'] : ['prohibited'],
            $key('employee_number') => ['nullable', 'string', 'max:80'], $key('code') => ['nullable', 'string', 'max:80'],
            $key('first_name') => ['required', 'string', 'max:150'], $key('middle_name') => ['nullable', 'string', 'max:150'], $key('last_name') => ['nullable', 'string', 'max:150'], $key('display_name') => ['nullable', 'string', 'max:255'],
            $key('email') => ['nullable', 'email', 'max:255'], $key('phone') => ['nullable', 'string', 'max:50'], $key('mobile') => ['nullable', 'string', 'max:50'],
            $key('department_id') => ['nullable', 'integer', 'min:1'], $key('designation_id') => ['nullable', 'integer', 'min:1'], $key('employment_type_id') => ['nullable', 'integer', 'min:1'], $key('reporting_manager_id') => ['nullable', 'integer', 'min:1'],
            $key('joined_date') => ['nullable', 'date'], $key('resigned_date') => ['nullable', 'date'], $key('date_of_birth') => ['nullable', 'date'],
            $key('gender') => ['nullable', Rule::enum(Gender::class)], $key('status') => ['nullable', Rule::enum(EmployeeStatus::class)], $key('availability_status') => ['nullable', Rule::enum(EmployeeAvailabilityStatus::class)],
            $key('default_hourly_rate') => ['nullable', 'decimal:0,6', 'gte:0'], $key('default_daily_rate') => ['nullable', 'decimal:0,6', 'gte:0'], $key('default_service_rate') => ['nullable', 'decimal:0,6', 'gte:0'],
            $key('notes') => ['nullable', 'string'], $key('metadata') => ['nullable', 'array'],
        ];
    }
}
