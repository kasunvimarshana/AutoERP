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
            'user_id' => 'integer',
            'department_id' => 'integer',
            'designation_id' => 'integer',
            'employment_type_id' => 'integer',
            'hire_date' => 'date',
            'confirmation_date' => 'date',
            'termination_date' => 'date',
            'country_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}