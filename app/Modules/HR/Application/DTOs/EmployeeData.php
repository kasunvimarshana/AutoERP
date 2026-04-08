<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class EmployeeData extends BaseDto
{
    public int $tenant_id;

    public string $first_name;

    public string $last_name;

    public string $email;

    public ?string $phone = null;

    public ?string $date_of_birth = null;

    public ?string $gender = null;

    public ?string $address = null;

    public string $employee_number;

    public string $hire_date;

    public string $employment_type = 'full_time';

    public string $status = 'active';

    public ?int $department_id = null;

    public ?int $position_id = null;

    public ?int $manager_id = null;

    public ?float $salary = null;

    public string $currency = 'USD';

    public ?int $org_unit_id = null;

    public ?array $metadata = null;

    public bool $is_active = true;

    public ?int $user_id = null;

    public function rules(): array
    {
        return [
            'tenant_id'       => 'required|integer',
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'date_of_birth'   => 'nullable|date',
            'gender'          => 'nullable|string|in:male,female,other',
            'address'         => 'nullable|string',
            'employee_number' => 'required|string|max:50',
            'hire_date'       => 'required|date',
            'employment_type' => 'required|string|in:full_time,part_time,contract,intern',
            'status'          => 'required|string|in:active,inactive,on_leave,terminated',
            'department_id'   => 'nullable|integer',
            'position_id'     => 'nullable|integer',
            'manager_id'      => 'nullable|integer',
            'salary'          => 'nullable|numeric|min:0',
            'currency'        => 'required|string|max:3',
            'org_unit_id'     => 'nullable|integer',
            'metadata'        => 'nullable|array',
            'is_active'       => 'boolean',
            'user_id'         => 'nullable|integer',
        ];
    }
}
