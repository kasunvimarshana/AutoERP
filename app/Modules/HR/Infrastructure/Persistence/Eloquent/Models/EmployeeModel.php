<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class EmployeeModel extends BaseModel
{
    protected $table = 'hr_employees';
    protected $fillable = [
        'tenant_id', 'user_id', 'department_id', 'position_id',
        'employee_code', 'first_name', 'last_name', 'email', 'phone',
        'gender', 'date_of_birth', 'hire_date', 'termination_date',
        'status', 'base_salary', 'bank_account', 'tax_id', 'address',
        'emergency_contact_name', 'emergency_contact_phone', 'metadata',
    ];
    protected $casts = [
        'id'            => 'int',
        'tenant_id'     => 'int',
        'user_id'       => 'int',
        'department_id' => 'int',
        'position_id'   => 'int',
        'base_salary'   => 'float',
        'metadata'      => 'array',
        'date_of_birth'     => 'datetime',
        'hire_date'         => 'datetime',
        'termination_date'  => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];
}
