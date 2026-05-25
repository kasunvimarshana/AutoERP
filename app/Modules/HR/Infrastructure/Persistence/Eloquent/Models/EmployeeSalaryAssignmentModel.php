<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class EmployeeSalaryAssignmentModel extends CoreModel
{


    protected $table = 'employee_salary_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'salary_structure_id' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'base_salary' => 'decimal:4',
            'created_by' => 'integer',
        ]);
    }
}