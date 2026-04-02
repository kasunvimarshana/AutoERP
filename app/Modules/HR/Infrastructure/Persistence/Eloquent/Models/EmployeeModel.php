<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class EmployeeModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_employees';

    protected $fillable = [
        'tenant_id', 'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'gender', 'address', 'employee_number',
        'hire_date', 'employment_type', 'status', 'department_id',
        'position_id', 'manager_id', 'salary', 'currency',
        'org_unit_id', 'metadata', 'is_active', 'user_id',
    ];

    protected $casts = [
        'tenant_id'     => 'integer',
        'department_id' => 'integer',
        'position_id'   => 'integer',
        'manager_id'    => 'integer',
        'org_unit_id'   => 'integer',
        'user_id'       => 'integer',
        'salary'        => 'float',
        'is_active'     => 'boolean',
        'metadata'      => 'array',
        'hire_date'     => 'date',
        'date_of_birth' => 'date',
    ];
}
