<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class EmployeeModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'department_id' => 'integer',
            'designation_id' => 'integer',
            'employment_type_id' => 'integer',
            'reporting_manager_id' => 'integer',
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'leaving_date' => 'date',
            'confirmation_date' => 'date',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'activated_by' => 'integer',
            'deactivated_by' => 'integer',
            'suspended_by' => 'integer',
            'terminated_by' => 'integer',
            'archived_by' => 'integer',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'terminated_at' => 'datetime',
            'archived_at' => 'datetime',
        ]);
    }
}
