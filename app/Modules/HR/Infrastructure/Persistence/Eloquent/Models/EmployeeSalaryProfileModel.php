<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class EmployeeSalaryProfileModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'employee_salary_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'basic_salary' => 'decimal:4',
            'hourly_rate' => 'decimal:4',
            'overtime_rate' => 'decimal:4',
            'payment_method_id' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
