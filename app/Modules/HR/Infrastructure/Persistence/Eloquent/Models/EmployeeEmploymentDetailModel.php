<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class EmployeeEmploymentDetailModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'employee_employment_details';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'department_id' => 'integer',
            'designation_id' => 'integer',
            'employment_type_id' => 'integer',
            'joining_date' => 'date',
            'probation_end_date' => 'date',
            'confirmation_date' => 'date',
            'leaving_date' => 'date',
            'reporting_manager_id' => 'integer',
            'work_location_id' => 'integer',
            'shift_id' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
